<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$cart_count   = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$user_logged  = isset($_SESSION['user_id']);
?>

<header class="nav-wrapper" id="mainNav">
  <nav class="nav container">
    <!-- Logo -->
    <a href="/index.php" class="nav__logo">
      <span class="nav__logo-icon">🍽</span>
      <span class="nav__logo-text">
        <span class="nav__logo-brand">Feast</span><span class="nav__logo-accent">ly</span>
      </span>
    </a>

    <!-- Desktop Links -->
    <ul class="nav__links hide-mobile">
      <li><a href="/menu.php"     class="nav__link <?= $current_page==='menu'?'active':'' ?>">Menu</a></li>
      <li><a href="/about.php"    class="nav__link <?= $current_page==='about'?'active':'' ?>">About</a></li>
      <li><a href="/contact.php"  class="nav__link <?= $current_page==='contact'?'active':'' ?>">Contact</a></li>
    </ul>

    <!-- Actions -->
    <div class="nav__actions">
      <button class="nav__cart-btn btn btn-ghost btn--icon" id="cartToggle" aria-label="Cart">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <?php if ($cart_count > 0): ?>
          <span class="nav__cart-badge"><?= $cart_count ?></span>
        <?php endif; ?>
      </button>

      <?php if ($user_logged): ?>
        <a href="/dashboard.php" class="btn btn-primary btn--sm hide-mobile">Dashboard</a>
        <div class="nav__user-menu">
          <button class="nav__avatar" id="userMenuToggle">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
          </button>
          <div class="nav__dropdown" id="userDropdown">
            <a href="/dashboard.php">Profile & Orders</a>
            <div class="divider" style="margin: 8px 0;"></div>
            <a href="/logout.php" class="text-coral">Sign Out</a>
          </div>
        </div>
      <?php else: ?>
        <a href="/login.php"    class="btn btn-secondary btn--sm hide-mobile">Sign In</a>
        <a href="/register.php" class="btn btn-primary btn--sm hide-mobile">Get Started</a>
      <?php endif; ?>

      <!-- Mobile hamburger -->
      <button class="nav__hamburger show-mobile" id="mobileMenuToggle" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- Mobile Menu Overlay -->
  <div class="nav__mobile-menu" id="mobileMenu">
    <ul>
      <li><a href="/menu.php">Menu</a></li>
      <li><a href="/about.php">About</a></li>
      <li><a href="/contact.php">Contact</a></li>
      <?php if (!$user_logged): ?>
        <li><a href="/login.php" class="btn btn-secondary" style="margin-top:8px;">Sign In</a></li>
        <li><a href="/register.php" class="btn btn-primary" style="margin-top:8px;">Get Started</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>
