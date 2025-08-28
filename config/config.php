<?php
declare(strict_types=1);

// Core configuration and utilities for the Printing Services System

// Paths
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('API_PATH', ROOT_PATH . '/api');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('ORDERS_JSON', STORAGE_PATH . '/orders.json');
define('ENV_PATH', ROOT_PATH . '/.env');

// Ensure storage directories/files
if (!is_dir(STORAGE_PATH)) {
  mkdir(STORAGE_PATH, 0775, true);
}
if (!is_dir(UPLOADS_PATH)) {
  mkdir(UPLOADS_PATH, 0775, true);
}
if (!file_exists(ORDERS_JSON)) {
  file_put_contents(ORDERS_JSON, json_encode([], JSON_PRETTY_PRINT));
}

// Load .env key=value pairs
function env_load(string $path): array {
  $vars = [];
  if (!file_exists($path)) {
    return $vars;
  }
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '#') || str_starts_with($trim, ';')) {
      continue;
    }
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
      $key = trim($parts[0]);
      $value = trim($parts[1]);
      $vars[$key] = $value;
    }
  }
  return $vars;
}

$ENV = env_load(ENV_PATH);

function env(string $key, ?string $default = null): string {
  global $ENV;
  return isset($ENV[$key]) ? $ENV[$key] : (string)($default ?? '');
}

// Session helper
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function is_authenticated(): bool {
  return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

function require_auth(): void {
  if (!is_authenticated()) {
    header('Location: /admin/index.php');
    exit;
  }
}

// Orders store
function orders_read(): array {
  $json = file_get_contents(ORDERS_JSON);
  $data = json_decode($json, true);
  if (!is_array($data)) {
    $data = [];
  }
  return $data;
}

function orders_write(array $orders): void {
  file_put_contents(ORDERS_JSON, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function orders_next_id(array $orders): int {
  $max = 0;
  foreach ($orders as $o) {
    if (isset($o['id']) && (int)$o['id'] > $max) {
      $max = (int)$o['id'];
    }
  }
  return $max + 1;
}

function sanitize_filename(string $name): string {
  $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
  return $name ?: 'file';
}

// Email notification using PHPMailer via Composer, with graceful fallback
function send_email_notification(string $subject, string $htmlBody): bool {
  $host = env('SMTP_HOST', 'smtp.gmail.com');
  $port = (int) env('SMTP_PORT', '587');
  $secure = env('SMTP_SECURE', 'tls');
  $username = env('SMTP_USER');
  $password = env('SMTP_PASS');
  $fromEmail = env('FROM_EMAIL', $username ?: 'no-reply@example.com');
  $fromName = env('FROM_NAME', 'Printing Services');
  $toEmail = env('ADMIN_EMAIL', $username ?: 'admin@example.com');

  $autoload = ROOT_PATH . '/vendor/autoload.php';
  $hasComposer = file_exists($autoload);

  try {
    if ($hasComposer) {
      require_once $autoload;
      $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
      $mailer->isSMTP();
      $mailer->Host = $host;
      $mailer->SMTPAuth = true;
      $mailer->Username = $username;
      $mailer->Password = $password;
      $mailer->SMTPSecure = $secure;
      $mailer->Port = $port;
      $mailer->setFrom($fromEmail, $fromName);
      $mailer->addAddress($toEmail);
      $mailer->isHTML(true);
      $mailer->Subject = $subject;
      $mailer->Body = $htmlBody;
      $mailer->AltBody = strip_tags($htmlBody);
      $mailer->send();
      return true;
    } else {
      // Native SMTP fallback (AUTH LOGIN + STARTTLS/SSL)
      $messageId = bin2hex(random_bytes(12)) . '@' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
      $headers = [];
      $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
      $headers[] = 'To: <' . $toEmail . '>';
      $headers[] = 'Subject: ' . $subject;
      $headers[] = 'MIME-Version: 1.0';
      $headers[] = 'Content-Type: text/html; charset=UTF-8';
      $headers[] = 'Message-ID: <' . $messageId . '>';
      $headers[] = 'Date: ' . date('r');
      $data = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n";

      $transport = $secure === 'ssl' ? 'ssl://' : '';
      $fp = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 10);
      if (!$fp) { throw new Exception('SMTP connect failed: ' . $errstr); }
      stream_set_timeout($fp, 10);

      $read = function() use ($fp) { return fgets($fp, 515); };
      $expect = function($codes) use ($fp, $read) {
        $line = $read();
        if ($line === false) throw new Exception('SMTP read failed');
        if (!is_array($codes)) $codes = [$codes];
        foreach ($codes as $c) {
          if (str_starts_with($line, (string)$c)) return $line;
        }
        throw new Exception('SMTP unexpected: ' . trim($line));
      };
      $cmd = function($s) use ($fp, $expect) { fwrite($fp, $s . "\r\n"); };

      $expect(['220']);
      $cmd('EHLO localhost');
      $ehlo = $expect(['250']);

      if ($secure !== 'ssl' && stripos($ehlo, 'STARTTLS') === false) {
        // Try to read multi-line EHLO
        $line = $ehlo;
        while ($line && isset($line[3]) && $line[3] === '-') {
          $line = fgets($fp, 515);
          if ($line && stripos($line, 'STARTTLS') !== false) { $ehlo .= $line; break; }
        }
      }

      if ($secure !== 'ssl' && stripos($ehlo, 'STARTTLS') !== false) {
        $cmd('STARTTLS');
        $expect('220');
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
          throw new Exception('Failed to enable TLS');
        }
        $cmd('EHLO localhost');
        $expect('250');
      }

      if ($username && $password) {
        $cmd('AUTH LOGIN');
        $expect('334');
        $cmd(base64_encode($username));
        $expect('334');
        $cmd(base64_encode($password));
        $expect('235');
      }

      $cmd('MAIL FROM: <' . $fromEmail . '>');
      $expect('250');
      $cmd('RCPT TO: <' . $toEmail . '>');
      $expect(['250','251']);
      $cmd('DATA');
      $expect('354');
      fwrite($fp, $data . "\r\n.\r\n");
      $expect('250');
      $cmd('QUIT');
      fclose($fp);
      return true;
    }
  } catch (Throwable $e) {
    // Fallback: log to file so the admin can review
    $logPath = STORAGE_PATH . '/mail.log';
    $log = date('c') . ' | ERROR: ' . $e->getMessage() . "\nSUBJECT: " . $subject . "\n" . $htmlBody . "\n\n";
    file_put_contents($logPath, $log, FILE_APPEND);
  }

  return false;
}

// Admin credentials
function check_admin_credentials(string $user, string $pass): bool {
  $expectedUser = env('ADMIN_USER', 'admin');
  $expectedPass = env('ADMIN_PASS', 'admin123');
  return hash_equals($expectedUser, $user) && hash_equals($expectedPass, $pass);
}

?>
