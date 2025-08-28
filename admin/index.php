<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = $_POST['username'] ?? '';
  $pass = $_POST['password'] ?? '';
  if (check_admin_credentials($user, $pass)) {
    $_SESSION['admin_authenticated'] = true;
    header('Location: /admin/dashboard.php');
    exit;
  } else {
    $error = 'Invalid credentials';
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Login</title>
  <link rel="stylesheet" href="/public/assets/css/style.css" />
  <style>.login { max-width: 360px; margin: 12vh auto; background: var(--card); border:1px solid var(--border); border-radius:12px; padding:20px; }</style>
</head>
<body>
  <div class="container">
    <div class="login">
      <h2>Admin Login</h2>
      <?php if (!empty($error)): ?>
      <div style="color:#ff8a8a; margin: 10px 0;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div style="display:grid; gap:12px;">
          <div>
            <label>Username</label>
            <input type="text" name="username" required style="width:100%; padding:10px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:8px; color:var(--text)" />
          </div>
          <div>
            <label>Password</label>
            <input type="password" name="password" required style="width:100%; padding:10px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:8px; color:var(--text)" />
          </div>
          <button class="btn primary" type="submit">Login</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

