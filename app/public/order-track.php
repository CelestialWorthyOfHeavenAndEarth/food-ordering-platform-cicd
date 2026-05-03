<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId === 0) {
    require_once __DIR__ . '/../src/config/Database.php';
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $latest = $stmt->fetchColumn();
    if ($latest) {
        $orderId = (int)$latest;
    } else {
        header('Location: /dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Order #<?= $orderId ?> — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <style>
    .tracker-container { max-width: 600px; margin: 100px auto; padding: 2rem; background: var(--surface-light); border-radius: 12px; text-align: center; }
    .status-pipeline { display: flex; justify-content: space-between; position: relative; margin: 2rem 0; }
    .status-pipeline::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 4px; background: rgba(255,255,255,0.1); z-index: 0; transform: translateY(-50%); }
    .status-step { width: 14px; height: 14px; background: #333; border-radius: 50%; position: relative; z-index: 1; transition: all 0.3s; font-size: 0; }
    .status-step::after { content: attr(data-label); position: absolute; top: 25px; left: 50%; transform: translateX(-50%); font-size: 0.75rem; white-space: nowrap; color: var(--text-muted); }
    .status-step.active { background: var(--accent-amber); box-shadow: 0 0 10px var(--accent-amber); }
    .status-step.active::after { color: #fff; }
    .status-step.current { transform: scale(1.5); }
    #status-label { font-size: 1.5rem; font-weight: bold; margin-top: 2rem; color: var(--accent-amber); }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <div class="tracker-container card reveal">
    <h2>Order #<?= $orderId ?> Status</h2>
    <div class="status-pipeline">
      <?php foreach(['placed','confirmed','preparing','cooking','out_for_delivery','delivered'] as $s): ?>
        <div class="status-step" id="step-<?= $s ?>" data-label="<?= ucwords(str_replace('_',' ',$s)) ?>"></div>
      <?php endforeach; ?>
    </div>
    <p id="status-label">Loading...</p>
  </div>

  <script>
  const orderId   = <?= $orderId ?>;
  const statuses  = ['placed','confirmed','preparing','cooking','out_for_delivery','delivered'];
  const emojis    = { placed:'📋', confirmed:'✅', preparing:'🥘', cooking:'🔥',
                      out_for_delivery:'🛵', delivered:'🎉' };

  function updateTracker(status) {
    if(status === 'not_found') {
        document.getElementById('status-label').textContent = 'Order Not Found';
        clearInterval(poller);
        return;
    }
    
    document.getElementById('status-label').textContent = emojis[status] + ' ' + status.replace(/_/g,' ').replace(/\b\w/g, l => l.toUpperCase());
    statuses.forEach(s => {
      const el = document.getElementById(`step-${s}`);
      el.classList.toggle('active',   statuses.indexOf(s) <= statuses.indexOf(status));
      el.classList.toggle('current',  s === status);
    });
    if (status === 'delivered') clearInterval(poller);
  }

  const poller = setInterval(async () => {
    try {
      const res  = await fetch(`/api/order-status.php?order_id=${orderId}`);
      const data = await res.json();
      if (data.status) updateTracker(data.status);
    } catch(e) {
      console.error(e);
    }
  }, 4000);
  
  // Initial fetch
  fetch(`/api/order-status.php?order_id=${orderId}`)
    .then(r => r.json())
    .then(d => d.status && updateTracker(d.status));
  </script>
</body>
</html>
