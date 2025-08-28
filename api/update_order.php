<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['message' => 'Method not allowed']);
  exit;
}

if (!is_authenticated()) {
  http_response_code(401);
  echo json_encode(['message' => 'Unauthorized']);
  exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';

if ($id <= 0 || !in_array($status, ['new','processing','completed','cancelled'], true)) {
  http_response_code(400);
  echo json_encode(['message' => 'Invalid request']);
  exit;
}

$orders = orders_read();
$found = false;
foreach ($orders as &$o) {
  if ((int)$o['id'] === $id) {
    $o['status'] = $status;
    $found = true;
    break;
  }
}
unset($o);

if (!$found) {
  http_response_code(404);
  echo json_encode(['message' => 'Order not found']);
  exit;
}

orders_write($orders);
echo json_encode(['ok' => true]);
exit;

