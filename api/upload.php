<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['message' => 'Method not allowed']);
  exit;
}

$items = $_POST['items'] ?? null;
// We accept structured arrays as items[0][field] style. Build from POST arrays.
$parsedItems = [];
if (is_array($_POST['items'] ?? null)) {
  // If the client sent JSON mistakenly, handle it
  $parsedItems = $_POST['items'];
} else {
  // Build from items[index][field] pattern
  foreach ($_POST as $key => $value) {
    if (preg_match('/^items\[(\d+)\]\[(.+)\]$/', (string)$key, $m)) {
      $idx = (int)$m[1];
      $field = $m[2];
      if (!isset($parsedItems[$idx])) $parsedItems[$idx] = [];
      $parsedItems[$idx][$field] = $value;
    }
  }
}

if (!$parsedItems) {
  http_response_code(400);
  echo json_encode(['message' => 'No items provided']);
  exit;
}

$savedFiles = [];
if (!empty($_FILES['files'])) {
  foreach ($_FILES['files']['error'] as $i => $err) {
    if ($err === UPLOAD_ERR_OK) {
      $tmp = $_FILES['files']['tmp_name'][$i];
      $orig = sanitize_filename($_FILES['files']['name'][$i]);
      $dest = UPLOADS_PATH . '/' . time() . '-' . bin2hex(random_bytes(4)) . '-' . $orig;
      if (!move_uploaded_file($tmp, $dest)) {
        continue;
      }
      $savedFiles[] = [ 'path' => $dest, 'name' => $orig ];
    }
  }
}

// Attach files to parsed items by index order
foreach ($parsedItems as $i => &$it) {
  if (isset($savedFiles[$i])) {
    $it['file'] = $savedFiles[$i];
  }
  $it['price'] = (float)($it['price'] ?? 0);
  $it['quantity'] = (int)($it['quantity'] ?? 1);
  $it['pages'] = (int)($it['pages'] ?? 1);
  $it['isBackToBack'] = isset($it['isBackToBack']) && ((string)$it['isBackToBack'] === '1');
}
unset($it);

$orders = orders_read();
$id = orders_next_id($orders);
$order = [
  'id' => $id,
  'createdAt' => date('c'),
  'note' => (string)($_POST['note'] ?? ''),
  'items' => $parsedItems,
  'status' => 'new',
  'seen' => false
];
$orders[] = $order;
orders_write($orders);

// Email notification
$subject = 'New Printing Request #' . $id;
$html = '<h2>New Printing Request</h2>' .
        '<p>Order ID: #' . $id . '</p>' .
        '<p>Items: ' . count($parsedItems) . '</p>' .
        '<p>Note: ' . htmlspecialchars($order['note']) . '</p>';
send_email_notification($subject, $html);

echo json_encode(['id' => $id, 'message' => 'Order created']);
exit;

