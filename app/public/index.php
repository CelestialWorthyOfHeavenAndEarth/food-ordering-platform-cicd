<?php
session_start([
    'cookie_secure'   => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../src/controllers/MenuController.php';
require_once __DIR__ . '/../src/config/Database.php';

$menuController = new MenuController();
$featured_items = $menuController->getFeatured(6);

// Load restaurants from DB
$db = Database::getConnection();
$restaurants = $db->query("SELECT * FROM restaurants WHERE is_active = 1 ORDER BY avg_rating DESC")->fetchAll(PDO::FETCH_ASSOC);

$user_logged = isset($_SESSION['user_id']);
$is_admin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* ===== RESTAURANT CARDS ===== */
    .restaurant-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
    .restaurant-card { background: var(--surface-light); border-radius: 16px; overflow: hidden; border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; position: relative; cursor: pointer; }
    .restaurant-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.4); }
    .restaurant-card__header { padding: 1.5rem 1.5rem 0; display: flex; justify-content: space-between; align-items: flex-start; }
    .restaurant-card__body { padding: 1rem 1.5rem 1.5rem; }
    .restaurant-card__name { font-size: 1.2rem; font-weight: 700; color: var(--text); margin: 0 0 0.25rem; }
    .restaurant-card__meta { display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; flex-wrap: wrap; }
    .restaurant-card__meta span { display: flex; align-items: center; gap: 4px; }
    .eco-badge { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .rating-badge { background: var(--accent-amber); color: #000; padding: 3px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
    .restaurant-card__dishes { display: flex; flex-direction: column; gap: 0.5rem; }
    .dish-row { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-top: 1px solid var(--border); font-size: 0.9rem; }
    .dish-row__name { color: var(--text-muted); }
    .dish-row__right { display: flex; align-items: center; gap: 0.75rem; }
    .dish-row__price { color: var(--accent-amber); font-weight: 600; }
    .dish-row__add { background: var(--accent-coral); color: white; border: none; border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; cursor: pointer; transition: opacity 0.2s; }
    .dish-row__add:hover { opacity: 0.85; }

    /* ===== ADMIN PANEL SECTION ===== */
    .admin-section { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border: 1px solid rgba(139,92,246,0.3); border-radius: 20px; padding: 2rem; margin: 2rem 0; }
    .admin-section h2 { color: #a78bfa; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; }
    .insights-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .stat-card { background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1.25rem; text-align: center; border: 1px solid rgba(255,255,255,0.08); }
    .stat-card__value { font-size: 2rem; font-weight: 800; color: var(--accent-amber); }
    .stat-card__label { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem; }
    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    .chart-box { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 1.25rem; border: 1px solid rgba(255,255,255,0.06); }
    .chart-box h4 { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; }
    .alert-list { margin-top: 1.5rem; }
    .alert-item { background: rgba(239,68,68,0.1); border-left: 3px solid #ef4444; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin-bottom: 0.5rem; font-size: 0.9rem; }
    @media(max-width:768px){ .charts-grid { grid-template-columns: 1fr; } }

    /* ===== FEATURE STRIP ===== */
    .feature-strip { display: flex; gap: 1rem; flex-wrap: wrap; margin: 1.5rem 0; }
    .feature-pill { display: flex; align-items: center; gap: 0.5rem; background: var(--surface-light); border: 1px solid var(--border); border-radius: 30px; padding: 0.5rem 1rem; font-size: 0.85rem; color: var(--text-muted); transition: all 0.2s; cursor: pointer; }
    .feature-pill:hover, .feature-pill.active { background: var(--accent-amber); color: #000; border-color: var(--accent-amber); }

    /* ===== ORDER TRACKER BANNER ===== */
    .tracker-banner { background: linear-gradient(135deg, #065f46, #047857); border-radius: 16px; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
    .tracker-banner h3 { color: #d1fae5; margin: 0; }
    .tracker-banner p { color: #6ee7b7; margin: 0.25rem 0 0; font-size: 0.9rem; }
  </style>
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
          <!-- Feature Pills -->
          <div class="feature-strip">
            <a href="/meal-builder.php" class="feature-pill">🍱 Build Your Meal</a>
            <a href="/order-track.php" class="feature-pill">📦 Track Order</a>
            <span class="feature-pill" onclick="toggleEcoMode()">🌿 Eco Mode</span>
            <a href="/menu.php" class="feature-pill">🍽️ Full Menu</a>
            <?php if ($is_admin): ?>
            <a href="#admin-panel" class="feature-pill active">📊 Admin Panel</a>
            <?php endif; ?>
          </div>
          <div class="hero__actions">
            <a href="/menu.php" class="btn btn-primary btn--lg">Order Now →</a>
            <a href="#restaurants" class="btn btn-secondary btn--lg">Browse Restaurants</a>
          </div>
          <div class="hero__stats">
            <div class="hero__stat"><strong><?= count($restaurants) ?></strong><span>Restaurants</span></div>
            <div class="hero__stat-divider"></div>
            <div class="hero__stat"><strong>4.5★</strong><span>Avg Rating</span></div>
            <div class="hero__stat-divider"></div>
            <div class="hero__stat"><strong>25min</strong><span>Avg Delivery</span></div>
          </div>
        </div>
        <div class="hero__visual animate-scale-in">
          <div class="hero__plate-container">
            <div class="hero__plate-ring hero__plate-ring--outer"></div>
            <div class="hero__plate-ring hero__plate-ring--inner"></div>
            <img src="/assets/images/hero-dish.png" alt="Featured dish" class="hero__plate-img"
              style="mask-image: radial-gradient(circle, black 65%, transparent 72%); -webkit-mask-image: radial-gradient(circle, black 65%, transparent 72%);"
              onerror="this.style.display='none'">
          </div>
          <div class="hero__float hero__float--rating animate-fade-up" style="animation-delay:0.4s">
            <span>⭐</span><strong>4.9</strong><small>Top Rated</small>
          </div>
          <div class="hero__float hero__float--delivery animate-fade-up" style="animation-delay:0.6s">
            <span>⏱</span><strong>25 min</strong><small>Delivery</small>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- SMART SEARCH RESULTS (Feature 7) are inline in nav, this is the order tracker banner -->
  <?php if ($user_logged): ?>
  <div class="container" style="margin-top: 2rem;">
    <div class="tracker-banner">
      <div>
        <h3>📦 Track Your Order</h3>
        <p>Real-time status: Preparing → Cooking → Out for Delivery → Delivered</p>
      </div>
      <a href="/order-track.php" class="btn btn-primary">View Order Status</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- AI RECOMMENDATIONS (Feature 1) — for logged-in users -->
  <?php if ($user_logged): ?>
  <section id="recommendations" class="section-gap" style="background: var(--surface-light);">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-amber">For You</span>
        <h2>✨ Recommended For You</h2>
        <p>Based on your history, time of day, and current weather in Chennai.</p>
      </div>
      <div id="rec-cards" class="menu-grid stagger">
        <p id="rec-loading" style="color:var(--text-muted);">Loading recommendations…</p>
      </div>
    </div>
  </section>
  <script>
  (async () => {
    const userId = <?= (int)$_SESSION['user_id'] ?>;
    try {
      const res  = await fetch(`/api/recommend.php?user_id=${userId}`);
      const data = await res.json();
      const grid = document.getElementById('rec-cards');
      document.getElementById('rec-loading')?.remove();
      if (data && data.length) {
        grid.innerHTML = data.map(dish => `
          <div class="menu-card card reveal">
            <div class="menu-card__image-wrap">
              <img src="${dish.image_url || '/assets/images/main-dish.png'}" alt="${dish.dish_name}" class="menu-card__image" onerror="this.src='/assets/images/main-dish.png'">
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
    } catch(e) {
      document.getElementById('recommendations').style.display = 'none';
    }
  })();
  </script>
  <?php endif; ?>

  <!-- RESTAURANTS SECTION (Feature 2 - Smart Nearby Ranking) -->
  <section class="section-gap" id="restaurants">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-coral">Smart Ranking</span>
        <h2>🏪 Our Restaurants</h2>
        <p>Ranked by ratings, eco-friendliness, and delivery speed. Browse menus and order directly.</p>
      </div>

      <!-- Eco filter toggle -->
      <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
        <button onclick="filterRestaurants('all')" id="filter-all" class="btn btn-secondary btn--sm" style="border-color:var(--accent-amber); color:var(--accent-amber);">All Restaurants</button>
        <button onclick="filterRestaurants('eco')" id="filter-eco" class="btn btn-secondary btn--sm">🌿 Eco-Friendly Only</button>
      </div>

      <div class="restaurant-grid" id="restaurant-list">
        <?php foreach ($restaurants as $r):
          $stmt = $db->prepare("SELECT m.id, m.name, m.price, m.is_veg FROM menu_items m WHERE m.restaurant_id = ? LIMIT 5");
          $stmt->execute([$r['id']]);
          $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="restaurant-card reveal" data-eco="<?= $r['is_eco_friendly'] ? '1' : '0' ?>">
          <div class="restaurant-card__header">
            <div>
              <h3 class="restaurant-card__name"><?= htmlspecialchars($r['name']) ?></h3>
              <div class="restaurant-card__meta">
                <span>⏱ <?= $r['avg_delivery_time'] ?> mins</span>
                <?php if ($r['is_eco_friendly']): ?>
                <span class="eco-badge">🌿 Eco</span>
                <?php endif; ?>
              </div>
            </div>
            <span class="rating-badge">★ <?= number_format($r['avg_rating'], 1) ?></span>
          </div>
          <div class="restaurant-card__body">
            <?php if ($r['is_eco_friendly'] && $r['eco_score'] > 0): ?>
            <p style="font-size:0.8rem; color:#22c55e; margin-bottom: 0.75rem;">🌍 Eco Score: <?= $r['eco_score'] ?>/100 — Minimal packaging, sustainable practices</p>
            <?php endif; ?>
            <div class="restaurant-card__dishes">
              <?php foreach ($dishes as $dish): ?>
              <div class="dish-row">
                <span class="dish-row__name">
                  <?= $dish['is_veg'] ? '🟢' : '🔴' ?> <?= htmlspecialchars($dish['name']) ?>
                </span>
                <div class="dish-row__right">
                  <span class="dish-row__price">₹<?= number_format($dish['price'], 0) ?></span>
                  <button class="dish-row__add" onclick="Cart.addItem(<?= $dish['id'] ?>, '<?= addslashes($dish['name']) ?>', <?= $dish['price'] ?>)">+ Add</button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            <a href="/menu.php?restaurant=<?= $r['id'] ?>" class="btn btn-secondary btn--sm" style="width:100%; margin-top: 1rem; text-align:center;">View Full Menu →</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- FEATURED DISHES -->
  <section class="featured section-gap" style="background: var(--surface-light);">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-amber">Today's Picks</span>
        <h2>🔥 Featured Dishes</h2>
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

  <!-- HOW IT WORKS -->
  <section class="how-it-works section-gap" id="how-it-works">
    <div class="container">
      <div class="section-header reveal">
        <span class="badge badge-coral">Simple Process</span>
        <h2>Order in 3 easy steps</h2>
        <p>From choosing your meal to enjoying it — we make it seamless.</p>
      </div>
      <div class="steps stagger">
        <div class="step-card card reveal">
          <div class="step-card__num">01</div>
          <div class="step-card__icon">🔍</div>
          <h3>Browse &amp; Search</h3>
          <p>Explore restaurants, browse menus, or use smart search like "spicy chicken under 200".</p>
        </div>
        <div class="step-card card reveal">
          <div class="step-card__num">02</div>
          <div class="step-card__icon">🛒</div>
          <h3>Add to Cart</h3>
          <p>Pick your favourites, build combos with discounts, and load up your cart.</p>
        </div>
        <div class="step-card card reveal">
          <div class="step-card__num">03</div>
          <div class="step-card__icon">⚡</div>
          <h3>Fast Delivery</h3>
          <p>We predict delivery time using AI — fresh and hot to your doorstep.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ADMIN PANEL (Feature 3 + 9 — integrated for admin users) -->
  <?php if ($is_admin): ?>
  <section class="section-gap" id="admin-panel">
    <div class="container">
      <div class="admin-section">
        <h2>📊 Admin Intelligence Panel</h2>

        <!-- Stat Cards -->
        <div class="insights-grid" id="admin-stats">
          <div class="stat-card"><div class="stat-card__value" id="stat-orders">—</div><div class="stat-card__label">Total Orders</div></div>
          <div class="stat-card"><div class="stat-card__value" id="stat-revenue">—</div><div class="stat-card__label">Revenue (₹)</div></div>
          <div class="stat-card"><div class="stat-card__value" id="stat-users">—</div><div class="stat-card__label">Customers</div></div>
          <div class="stat-card"><div class="stat-card__value" id="stat-restaurants"><?= count($restaurants) ?></div><div class="stat-card__label">Restaurants</div></div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
          <div class="chart-box"><h4>Top Dishes (Last 30 Days)</h4><canvas id="topItemsChart"></canvas></div>
          <div class="chart-box"><h4>Peak Ordering Hours</h4><canvas id="peakHoursChart"></canvas></div>
          <div class="chart-box"><h4>Revenue Trend</h4><canvas id="revenueChart"></canvas></div>
          <div class="chart-box"><h4>Delivery Performance</h4><canvas id="deliveryChart"></canvas></div>
        </div>

        <!-- Alerts -->
        <div class="alert-list">
          <h3 style="color:#f87171; margin: 1.5rem 0 0.75rem;">🔔 Active Alerts</h3>
          <div id="admin-alerts-list"><p style="color:var(--text-muted);">Loading alerts…</p></div>
        </div>

        <div style="margin-top: 1.5rem; display:flex; gap: 1rem; flex-wrap: wrap;">
          <a href="/admin-insights.php" class="btn btn-primary">Full Analytics →</a>
          <a href="/order-track.php" class="btn btn-secondary">Order Management</a>
        </div>
      </div>
    </div>
  </section>

  <script>
  // Load admin insights
  fetch('/api/insights.php').then(r => r.json()).then(data => {
    if (data.top_items) {
      document.getElementById('stat-orders').textContent = data.top_items.reduce((a,i) => a + parseInt(i.order_count||0), 0) || '—';
    }
    if (data.revenue_trend && data.revenue_trend.length) {
      const total = data.revenue_trend.reduce((a, r) => a + parseFloat(r.revenue||0), 0);
      document.getElementById('stat-revenue').textContent = '₹' + Math.round(total).toLocaleString('en-IN');
    }

    const chartDefaults = { responsive: true, plugins: { legend: { labels: { color: '#aaa' } } }, scales: { x: { ticks: { color: '#aaa' } }, y: { ticks: { color: '#aaa' } } } };

    if (data.top_items?.length) {
      new Chart(document.getElementById('topItemsChart'), {
        type: 'bar',
        data: { labels: data.top_items.map(i => i.dish_name), datasets: [{ label: 'Orders', data: data.top_items.map(i => i.order_count), backgroundColor: '#e94560' }] },
        options: chartDefaults
      });
    }
    if (data.peak_hours?.length) {
      new Chart(document.getElementById('peakHoursChart'), {
        type: 'line',
        data: { labels: data.peak_hours.map(h => `${h.hour}:00`), datasets: [{ label: 'Orders', data: data.peak_hours.map(h => h.order_count), borderColor: '#a78bfa', fill: true, tension: 0.4 }] },
        options: chartDefaults
      });
    }
    if (data.revenue_trend?.length) {
      new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels: data.revenue_trend.map(r => r.date), datasets: [{ label: '₹ Revenue', data: data.revenue_trend.map(r => r.revenue), borderColor: '#e94560', fill: false, tension: 0.1 }] },
        options: chartDefaults
      });
    }
    if (data.delivery_perf?.length) {
      new Chart(document.getElementById('deliveryChart'), {
        type: 'bar',
        data: { labels: data.delivery_perf.map(r => r.name), datasets: [{ label: 'Avg Mins', data: data.delivery_perf.map(r => r.avg_time), backgroundColor: '#f59e0b' }] },
        options: chartDefaults
      });
    }
  }).catch(e => console.error('Insights error', e));

  // Load alerts
  fetch('/api/alerts.php?action=list').then(r => r.json()).then(data => {
    const container = document.getElementById('admin-alerts-list');
    if (data.alerts && data.alerts.length) {
      container.innerHTML = data.alerts.map(a => `<div class="alert-item">🔔 ${a.message} <small style="opacity:0.6;"> — ${a.created_at}</small></div>`).join('');
    } else {
      container.innerHTML = '<p style="color:#22c55e;">✅ No active alerts. System is healthy.</p>';
    }
  }).catch(() => {
    document.getElementById('admin-alerts-list').innerHTML = '<p style="color:var(--text-muted);">Could not load alerts.</p>';
  });

  // Load user count
  fetch('/api/insights.php').then(r => r.json()).then(data => {
    if (data.user_count !== undefined) document.getElementById('stat-users').textContent = data.user_count;
  });
  </script>
  <?php endif; ?>

  <!-- CART DRAWER -->
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>

  <!-- TOAST CONTAINER -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- FOOTER -->
  <footer style="background: var(--surface-light); border-top: 1px solid var(--border); padding: 2rem; text-align: center; color: var(--text-muted); margin-top: 4rem;">
    <div style="max-width: 800px; margin: 0 auto;">
      <p style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem;">🍽️ Feastly</p>
      <p>Authentic Andhra cuisine delivered fast. Made with ❤️ and lots of spice.</p>
      <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; font-size: 0.9rem;">
        <a href="/menu.php" style="color: var(--accent-amber);">Menu</a>
        <a href="/meal-builder.php" style="color: var(--accent-amber);">Build Combos</a>
        <a href="/order-track.php" style="color: var(--accent-amber);">Track Order</a>
        <?php if ($is_admin): ?>
        <a href="#admin-panel" style="color: #a78bfa;">Admin Panel</a>
        <a href="/admin-insights.php" style="color: #a78bfa;">Full Analytics</a>
        <?php endif; ?>
      </div>
    </div>
  </footer>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>

  <script>
  // Restaurant filter
  function filterRestaurants(type) {
    const cards = document.querySelectorAll('.restaurant-card');
    document.getElementById('filter-all').style.borderColor = type==='all' ? 'var(--accent-amber)' : '';
    document.getElementById('filter-all').style.color = type==='all' ? 'var(--accent-amber)' : '';
    document.getElementById('filter-eco').style.borderColor = type==='eco' ? '#22c55e' : '';
    document.getElementById('filter-eco').style.color = type==='eco' ? '#22c55e' : '';
    cards.forEach(card => {
      if (type === 'eco' && card.dataset.eco !== '1') {
        card.style.display = 'none';
      } else {
        card.style.display = 'block';
      }
    });
  }
  </script>
</body>
</html>
