<?php
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/helpers/Security.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::verify_csrf();
    $auth = new AuthController();
    $result = $auth->login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        header('Location: ' . ($result['redirect'] ?? '/index.php'));
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-xl) var(--space-md); margin-top: 60px;">
    <div class="card reveal" style="width: 100%; max-width: 400px; padding: var(--space-xl); background: rgba(20,20,20,0.8); backdrop-filter: blur(20px);">
      <div class="text-center" style="margin-bottom: var(--space-xl);">
        <h2 style="margin-bottom: var(--space-xs);">Welcome Back</h2>
        <p style="color: var(--text-secondary);">Sign in to continue to Feastly</p>
      </div>

      <?php if ($error): ?>
        <div style="background: rgba(239,68,68,0.1); color: #ef4444; padding: var(--space-sm); border-radius: var(--radius-md); margin-bottom: var(--space-md); text-align: center; border: 1px solid rgba(239,68,68,0.2);">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/login.php">
        <?= Security::csrf_field() ?>
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" name="email" class="form-input" required placeholder="you@example.com">
        </div>
        
        <div class="form-group">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <label class="form-label" for="password">Password</label>
            <a href="#" style="font-size: 0.875rem; color: var(--accent-amber); text-decoration: none;">Forgot?</a>
          </div>
          <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-md);">Sign In</button>
      </form>

      <div class="text-center" style="margin-top: var(--space-lg); padding-top: var(--space-md); border-top: 1px solid var(--border);">
        <p style="color: var(--text-secondary); font-size: 0.9rem;">
          Don't have an account? <a href="/register.php" style="color: var(--accent-amber); text-decoration: none; font-weight: 600;">Sign Up</a>
        </p>
      </div>
    </div>
  </div>

  <script src="/assets/js/main.js"></script>
</body>
</html>
