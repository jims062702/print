<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $orders = orders_read();
  $unseen = 0;
  foreach ($orders as $o) {
    if (isset($o['seen']) && !$o['seen']) $unseen++;
  }
  echo json_encode(['unseen' => $unseen, 'total' => count($orders)]);
  exit;
}

if ($method === 'POST') {
  // mark all as seen
  $orders = orders_read();
  foreach ($orders as &$o) { $o['seen'] = true; }
  unset($o);
  orders_write($orders);
  echo json_encode(['ok' => true]);
  exit;
}

http_response_code(405);
echo json_encode(['message' => 'Method not allowed']);
exit;

