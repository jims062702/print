<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_auth();

$orders = orders_read();
usort($orders, function($a,$b){ return strcmp($b['createdAt']??'', $a['createdAt']??''); });
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Dashboard</title>
  <link rel="stylesheet" href="/public/assets/css/style.css" />
  <style>
    .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .table th, .table td { border:1px solid var(--border); padding: 8px; vertical-align: top; }
    .row-actions { display:flex; gap:8px; }
    .file-list { color: var(--muted); font-size: 13px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="admin-header">
      <div class="logo"><img src="https://api.iconify.design/solar:printer-line-duotone.svg" alt="logo" /><span>Admin Dashboard</span></div>
      <div class="bell" id="bell">
        <span>🔔</span>
        <span class="badge" id="badge" style="display:none">0</span>
      </div>
      <a class="btn" href="/admin/logout.php">Logout</a>
    </div>

    <div class="card">
      <h3>Orders</h3>
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Created</th><th>Items</th><th>Note</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td>#<?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($o['createdAt'] ?? 'now'))) ?></td>
            <td>
              <div class="file-list">
              <?php foreach (($o['items'] ?? []) as $it): ?>
                <div>
                  <?= htmlspecialchars($it['name'] ?? 'file') ?>
                  <?php if (!empty($it['file']['path'])): ?>
                    — <a href="/admin/download.php?p=<?= urlencode(basename($it['file']['path'])) ?>">download</a>
                  <?php endif; ?>
                  • ₱<?= number_format((float)($it['price'] ?? 0), 2) ?>
                </div>
              <?php endforeach; ?>
              </div>
            </td>
            <td><?= nl2br(htmlspecialchars($o['note'] ?? '')) ?></td>
            <td><?= htmlspecialchars($o['status'] ?? 'new') ?></td>
            <td>
              <div class="row-actions">
                <form method="post" action="/api/update_order.php" onsubmit="return submitStatus(this)">
                  <input type="hidden" name="id" value="<?= (int)$o['id'] ?>" />
                  <select name="status">
                    <?php foreach (['new','processing','completed','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= (($o['status'] ?? '')===$s)?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn">Update</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    async function refreshBadge(){
      try{
        const r = await fetch('/api/notifications.php');
        const d = await r.json();
        const b = document.getElementById('badge');
        if (d.unseen > 0) { b.style.display='inline-block'; b.textContent = d.unseen; }
        else { b.style.display='none'; }
      }catch(e){}
    }
    async function clearBadge(){
      try{ await fetch('/api/notifications.php', { method: 'POST' }); refreshBadge(); }catch(e){}
    }
    function submitStatus(form){
      fetch(form.action, { method: 'POST', body: new FormData(form) }).then(r=>r.json()).then(()=>location.reload());
      return false;
    }
    document.getElementById('bell').addEventListener('click', clearBadge);
    refreshBadge();
    setInterval(refreshBadge, 6000);
  </script>
</body>
</html>

