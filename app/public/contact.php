<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process form
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Us — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <section class="section-gap" style="margin-top: 80px;">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-amber">Get In Touch</span>
        <h2>We'd Love to Hear From You</h2>
        <p>Have a question about your order, or want to provide feedback? Send us a message.</p>
      </div>

      <div class="card reveal" style="max-width: 600px; margin: 0 auto; padding: var(--space-xl); background: rgba(30,30,30,0.5);">
        <?php if ($success): ?>
          <div style="text-align: center; padding: var(--space-xl) 0;">
            <div style="margin-bottom: var(--space-md); color: #10b981;">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3>Message Sent!</h3>
            <p>Thank you for reaching out. Our team will get back to you shortly.</p>
            <a href="/" class="btn btn-primary" style="margin-top: var(--space-md);">Return Home</a>
          </div>
        <?php else: ?>
          <form method="POST" action="/contact.php" class="stagger">
            <div class="form-group">
              <label class="form-label" for="name">Full Name</label>
              <input type="text" id="name" name="name" class="form-input" required placeholder="John Doe">
            </div>
            
            <div class="form-group">
              <label class="form-label" for="email">Email Address</label>
              <input type="email" id="email" name="email" class="form-input" required placeholder="john@example.com">
            </div>
            
            <div class="form-group">
              <label class="form-label" for="message">Your Message</label>
              <textarea id="message" name="message" class="form-input" rows="5" required placeholder="How can we help you?"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary btn--lg" style="width: 100%;">Send Message</button>
          </form>
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
