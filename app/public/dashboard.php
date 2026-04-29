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

require_once __DIR__ . '/../src/config/Database.php';

// Fetch user orders
$stmt = Database::query("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
", [$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// If user requested logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <section class="section-gap" style="margin-top: 60px;">
    <div class="container">
      
      <?php if (isset($_GET['success'])): ?>
        <div style="background: rgba(16,185,129,0.1); color: #10b981; padding: var(--space-md); border-radius: var(--radius-md); margin-bottom: var(--space-xl); text-align: center; border: 1px solid rgba(16,185,129,0.2);">
          <strong>Order placed successfully!</strong> We are preparing your meal.
        </div>
      <?php endif; ?>

      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: var(--space-xl);">
        <div>
          <h2>My Profile</h2>
          <p style="color: var(--text-secondary);">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?></p>
        </div>
        <a href="?logout=1" class="btn btn-secondary btn--sm">Log Out</a>
      </div>

      <div class="card reveal" style="padding: var(--space-xl); background: rgba(20,20,20,0.8);">
        <h3 style="margin-bottom: var(--space-lg);">Order History</h3>
        
        <?php if (empty($orders)): ?>
          <div style="text-align: center; padding: var(--space-2xl) 0; color: var(--text-muted);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: var(--space-md);"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <p>You haven't placed any orders yet.</p>
            <a href="/menu.php" class="btn btn-primary" style="margin-top: var(--space-md);">Browse Menu</a>
          </div>
        <?php else: ?>
          <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
              <thead>
                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: var(--text-sm);">
                  <th style="padding: 12px 0;">Order ID</th>
                  <th style="padding: 12px 0;">Date</th>
                  <th style="padding: 12px 0;">Status</th>
                  <th style="padding: 12px 0;">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 16px 0; font-family: monospace;">#<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></td>
                    <td style="padding: 16px 0;"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></td>
                    <td style="padding: 16px 0;">
                      <span class="badge" style="background: rgba(245,158,11,0.1); color: var(--accent-amber); font-size: 0.75rem;">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $order['status']))) ?>
                      </span>
                    </td>
                    <td style="padding: 16px 0; font-weight: 600;">₹<?= number_format($order['total_amount'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- CART DRAWER -->
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>
  <div class="toast-container" id="toastContainer"></div>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
</body>
</html>
