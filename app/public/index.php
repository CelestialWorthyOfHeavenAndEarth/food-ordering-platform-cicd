<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF helper for forms
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            die('CSRF token mismatch');
        }
    }
}

require_once __DIR__ . '/../src/controllers/MenuController.php';
$menuController = new MenuController();
$featured_items = $menuController->getFeatured(3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Feastly — Order Delicious Food Online</title>
  <meta name="description" content="Order fresh, delicious food from the best local restaurants. Fast delivery, easy ordering, incredible taste.">
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <!-- HERO SECTION -->
  <section class="hero">
    <div class="hero__glow"></div>
    <div class="container">
      <div class="hero__content">
        <div class="hero__text animate-fade-up">
          <span class="badge badge-amber hero__eyebrow">🔥 Fresh & Hot Delivery</span>
          <h1 class="hero__title">
            Taste the best<br>
            <em class="hero__title-accent">food in town</em>
          </h1>
          <p class="hero__subtitle">
            Handcrafted dishes made with love, delivered to your door in under 30 minutes. 
            From spicy street food to gourmet experiences.
          </p>
          <div class="hero__actions">
            <a href="/menu.php" class="btn btn-primary btn--lg">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8.1 13.34l2.83-2.83L3.91 3.5a4.008 4.008 0 000 5.66l4.19 4.18zm6.78-1.81c1.53.71 3.68.21 5.27-1.38 1.91-1.91 2.28-4.65.81-6.12-1.46-1.46-4.2-1.1-6.12.81-1.59 1.59-2.09 3.74-1.38 5.27L3.7 19.87l1.41 1.41L12 14.41l6.88 6.88 1.41-1.41L13.41 13l1.47-1.47z"/></svg>
              Order Now
            </a>
            <a href="#how-it-works" class="btn btn-secondary btn--lg">How It Works</a>
          </div>
          <div class="hero__stats">
            <div class="hero__stat"><strong>500+</strong><span>Menu Items</span></div>
            <div class="hero__stat-divider"></div>
            <div class="hero__stat"><strong>4.9★</strong><span>Avg Rating</span></div>
            <div class="hero__stat-divider"></div>
            <div class="hero__stat"><strong>30min</strong><span>Avg Delivery</span></div>
          </div>
        </div>
        <div class="hero__visual animate-scale-in">
          <div class="hero__plate-container">
            <div class="hero__plate-ring hero__plate-ring--outer"></div>
            <div class="hero__plate-ring hero__plate-ring--inner"></div>
            <img 
              src="/assets/images/hero-dish.png" 
              alt="Featured dish" 
              class="hero__plate-img" 
              style="mask-image: radial-gradient(circle, black 65%, transparent 72%); -webkit-mask-image: radial-gradient(circle, black 65%, transparent 72%);"
              onerror="this.style.display='none'"
            >
          </div>
          <div class="hero__float hero__float--rating animate-fade-up" style="animation-delay:0.4s">
            <span class="icon-pulse"><svg width="20" height="20" viewBox="0 0 24 24" fill="var(--accent-amber)" stroke="var(--accent-amber)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></span>
            <strong>4.9</strong><small>Top Rated</small>
          </div>
          <div class="hero__float hero__float--delivery animate-fade-up" style="animation-delay:0.6s">
            <span class="icon-spin"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
            <strong>25 min</strong><small>Delivery</small>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- RECOMMENDATIONS (Feature 1) -->
  <?php if ($user_logged): ?>
  <section id="recommendations" class="recommendations-section section-gap" style="background: var(--surface-light);">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-amber">For You</span>
        <h2>✨ Recommended For You</h2>
        <p>Based on your history, time of day, and current weather.</p>
      </div>
      <div id="rec-cards" class="menu-grid stagger">
        <!-- JS will populate this -->
      </div>
    </div>
  </section>
  <script>
  (async () => {
    const userId = <?= $_SESSION['user_id'] ?>;
    if (!userId) return;
    try {
      const res  = await fetch(`/api/recommend.php?user_id=${userId}`);
      const data = await res.json();
      const grid = document.getElementById('rec-cards');
      if (data && data.length) {
          grid.innerHTML = data.map(dish => `
            <div class="menu-card card reveal">
              <div class="menu-card__image-wrap">
                <img src="${dish.image_url || '/assets/images/default.png'}" alt="${dish.dish_name}" class="menu-card__image">
              </div>
              <div class="menu-card__content">
                <h3 class="menu-card__title">${dish.dish_name}</h3>
                <div class="menu-card__meta">
                  <span class="menu-card__price">₹${dish.price}</span>
                </div>
                <button class="btn btn-primary btn--sm" onclick="Cart.addItem(${dish.id}, '${dish.dish_name.replace(/'/g,"\\'")}', ${dish.price})" style="width:100%; margin-top:1rem;">Add to Cart</button>
              </div>
            </div>`).join('');
      } else {
          document.getElementById('recommendations').style.display = 'none';
      }
    } catch (e) {
      console.error(e);
      document.getElementById('recommendations').style.display = 'none';
    }
  })();
  </script>
  <?php endif; ?>

  <!-- HOW IT WORKS -->
  <section class="how-it-works section-gap" id="how-it-works">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-coral">Simple Process</span>
        <h2>Order in 3 easy steps</h2>
        <p>From choosing your meal to enjoying it — we make it seamless.</p>
      </div>
      <div class="steps stagger">
        <?php
        $searchIcon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
        $cartIcon   = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>';
        $zapIcon    = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>';

        $steps = [
          ['icon'=>$searchIcon, 'num'=>'01','title'=>'Browse Menu','desc'=>'Explore hundreds of authentic Andhra dishes.'],
          ['icon'=>$cartIcon,   'num'=>'02','title'=>'Add to Cart',  'desc'=>'Pick your favourites, customize, and load up your cart.'],
          ['icon'=>$zapIcon,    'num'=>'03','title'=>'Fast Delivery', 'desc'=>'We prep fresh and deliver hot to your doorstep.'],
        ];
        foreach ($steps as $step): ?>
          <div class="step-card card reveal">
            <div class="step-card__num"><?= $step['num'] ?></div>
            <div class="step-card__icon"><?= $step['icon'] ?></div>
            <h3><?= $step['title'] ?></h3>
            <p><?= $step['desc'] ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- NEARBY RESTAURANTS (Feature 2) -->
  <section class="nearby-restaurants section-gap" id="nearby-restaurants" style="background: var(--surface-light); display: none;">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-amber">Smart Ranking</span>
        <h2>Nearby Kitchens</h2>
        <p>Ranked by ratings, delivery speed, and distance to you.</p>
      </div>
      <div id="ranked-restaurants" class="menu-grid stagger">
        <!-- JS will populate this -->
      </div>
    </div>
  </section>

  <!-- FEATURED MENU -->
  <section class="featured section-gap">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-amber">Today's Picks</span>
        <h2>Featured Dishes</h2>
        <p>Our chef's handpicked favourites — fresh, flavourful, and just for you.</p>
      </div>
      <div class="menu-grid stagger">
        <?php foreach ($featured_items as $item): ?>
          <?php include __DIR__ . '/../templates/partials/menu-card.php'; ?>
        <?php endforeach; ?>
      </div>
      <div class="text-center" style="margin-top: var(--space-2xl);">
        <a href="/menu.php" class="btn btn-secondary btn--lg">View Full Menu →</a>
      </div>
    </div>
  </section>

  <!-- CART DRAWER -->
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>

  <!-- TOAST CONTAINER -->
  <div class="toast-container" id="toastContainer"></div>

  <?php // include __DIR__ . '/../templates/layout/footer.php'; ?>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
  <script>
    function renderRestaurantList(data) {
      const cont = document.getElementById('ranked-restaurants');
      const section = document.getElementById('nearby-restaurants');
      if (!data || data.length === 0) return;
      section.style.display = 'block';
      cont.innerHTML = data.map(r => `
        <div class="menu-card card reveal restaurant-card" data-eco="${r.is_eco_friendly ? '1' : '0'}">
          ${r.is_eco_friendly ? '<span class="badge badge-amber" style="position:absolute; top:10px; right:10px; z-index:2;">🌿 Eco Friendly</span>' : ''}
          <div class="menu-card__content" style="padding: 1.5rem;">
            <h3 class="menu-card__title">${r.name}</h3>
            <p class="menu-card__desc">Distance: ${r.distance_km} km • Delivery: ${r.avg_delivery_time} mins</p>
            <div class="menu-card__meta">
              <span>★ ${r.avg_rating}</span>
              <span style="color: var(--accent-amber);">Score: ${r.smart_score}</span>
            </div>
            ${r.distance_km ? `<small class="carbon-impact" style="display:block; margin-top:10px; color:var(--text-muted);">~${(r.distance_km * 0.12).toFixed(2)} kg CO₂ delivery impact</small>` : ''}
          </div>
        </div>
      `).join('');
    }

    function loadRankedRestaurants() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(async (pos) => {
          const { latitude: lat, longitude: lon } = pos.coords;
          const res  = await fetch(`/api/rank-restaurants.php?lat=${lat}&lon=${lon}`);
          const data = await res.json();
          renderRestaurantList(data);
        }, async () => {
          // Fallback: load without geo
          const res = await fetch('/api/rank-restaurants.php');
          const data = await res.json();
          renderRestaurantList(data);
        });
      } else {
        fetch('/api/rank-restaurants.php').then(r => r.json()).then(renderRestaurantList);
      }
    }
    document.addEventListener('DOMContentLoaded', loadRankedRestaurants);
  </script>
</body>
</html>
