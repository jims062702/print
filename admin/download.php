<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_auth();

$basename = basename((string)($_GET['p'] ?? ''));
if ($basename === '') { http_response_code(404); exit('Not found'); }

// Find file in uploads by basename
$orders = orders_read();
$path = null;
foreach ($orders as $o) {
  foreach (($o['items'] ?? []) as $it) {
    if (!empty($it['file']['path']) && basename($it['file']['path']) === $basename) {
      $path = $it['file']['path'];
      break 2;
    }
  }
}

if (!$path || !file_exists($path)) { http_response_code(404); exit('Not found'); }

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $basename);
header('Content-Length: ' . filesize($path));
readfile($path);
exit;

