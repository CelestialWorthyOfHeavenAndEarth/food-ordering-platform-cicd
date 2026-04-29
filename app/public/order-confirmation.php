<?php
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-xl) var(--space-md); margin-top: 60px;">
    <div class="card reveal text-center" style="width: 100%; max-width: 500px; padding: var(--space-3xl) var(--space-xl); background: rgba(20,20,20,0.8); backdrop-filter: blur(20px);">
      <div style="font-size: 4rem; margin-bottom: var(--space-md); animation: floatY 4s ease infinite;">🎉</div>
      <h2 style="color: var(--accent-amber); margin-bottom: var(--space-sm);">Order Confirmed!</h2>
      <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">Thank you for your order. Our chefs are already preparing your delicious food.</p>
      
      <div style="background: var(--bg-body); padding: var(--space-md); border-radius: var(--radius-md); border: 1px dashed var(--border); margin-bottom: var(--space-xl);">
        <span style="color: var(--text-muted); font-size: var(--text-sm);">Order ID</span>
        <div style="font-size: var(--text-xl); font-weight: 700; color: var(--text-primary); margin-top: 4px;">#<?= htmlspecialchars($orderId) ?></div>
      </div>

      <a href="/dashboard.php" class="btn btn-primary" style="width: 100%; margin-bottom: var(--space-sm);">Track My Order</a>
      <a href="/menu.php" class="btn btn-secondary" style="width: 100%;">Return to Menu</a>
    </div>
  </div>

  <?php include __DIR__ . '/../templates/layout/footer.php'; ?>
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
</body>
</html>
