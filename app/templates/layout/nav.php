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
      <li><a href="/meal-builder.php" class="nav__link <?= $current_page==='meal-builder'?'active':'' ?>">Combos</a></li>
      <li><a href="/about.php"    class="nav__link <?= $current_page==='about'?'active':'' ?>">About</a></li>
      <li><a href="/contact.php"  class="nav__link <?= $current_page==='contact'?'active':'' ?>">Contact</a></li>
    </ul>

    <!-- Smart Search -->
    <div class="smart-search hide-mobile" style="position:relative; margin-left:1rem; margin-right:1rem; flex-grow:1; max-width:300px;">
      <input type="text" id="search-input" placeholder="Try 'spicy chicken under 200'..." style="width:100%; padding:8px 12px; border-radius:20px; border:1px solid var(--border); background:var(--surface); color:var(--text); font-size:0.9rem;">
      <div id="search-results" class="results-dropdown" style="position:absolute; top:100%; left:0; right:0; background:var(--surface-light); border-radius:8px; margin-top:8px; box-shadow:0 4px 12px rgba(0,0,0,0.5); z-index:100; max-height:400px; overflow-y:auto; display:none;"></div>
    </div>

    <!-- Actions -->
    <div class="nav__actions">
      <?php if (!$user_logged || ($_SESSION['role']??'') !== 'admin'): ?>
      <button class="nav__cart-btn btn btn-ghost btn--icon" id="cartToggle" aria-label="Cart">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <?php if ($cart_count > 0): ?>
          <span class="nav__cart-badge"><?= $cart_count ?></span>
        <?php endif; ?>
      </button>
      <?php endif; ?>

      <!-- Eco Mode Toggle -->
      <?php if (!$user_logged || ($_SESSION['role']??'') !== 'admin'): ?>
      <button id="eco-toggle" class="btn btn-ghost hide-mobile" onclick="toggleEcoMode()" style="font-size:0.9rem; padding: 4px 8px;">🌱 Eco: OFF</button>
      <?php endif; ?>

      <!-- Admin Alert Badge -->
      <div id="alert-badge" style="display:none; margin-left: 10px;" class="alert-icon">
        <a href="/admin-insights.php" style="text-decoration:none;">🔔 <span id="alert-count" style="background:var(--accent-coral); color:white; border-radius:50%; padding:2px 6px; font-size:0.75rem;">0</span></a>
      </div>

      <?php if ($user_logged): ?>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="/admin-orders.php" class="btn btn-ghost btn--sm hide-mobile" style="color:#a78bfa; border:1px solid #a78bfa;">📦 Orders</a>
          <a href="/admin-insights.php" class="btn btn-ghost btn--sm hide-mobile" style="color:#a78bfa; border:1px solid #a78bfa;">📊 Admin</a>
        <?php endif; ?>
        <a href="/dashboard.php" class="btn btn-primary btn--sm hide-mobile">Dashboard</a>
        <div class="nav__user-menu">
          <button class="nav__avatar" id="userMenuToggle">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
          </button>
          <div class="nav__dropdown" id="userDropdown">
            <a href="/dashboard.php">Profile &amp; Orders</a>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/admin-insights.php">Full Analytics</a>
            <?php endif; ?>
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
      <li><a href="/meal-builder.php">Combos</a></li>
      <li><a href="/about.php">About</a></li>
      <li><a href="/contact.php">Contact</a></li>
      <?php if (!$user_logged): ?>
        <li><a href="/login.php" class="btn btn-secondary" style="margin-top:8px;">Sign In</a></li>
        <li><a href="/register.php" class="btn btn-primary" style="margin-top:8px;">Get Started</a></li>
      <?php endif; ?>
    </ul>
  </div>
</header>

<style>
.smart-search .result-item { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); cursor: pointer; transition: background 0.2s; }
.smart-search .result-item:hover { background: rgba(255,255,255,0.05); }
.smart-search .result-item strong { display: block; color: var(--accent-amber); margin-bottom: 4px; }
.smart-search .result-item small { display: block; font-size: 0.75rem; color: var(--text-muted); }
</style>

<script>
let debounceTimer;
document.getElementById('search-input')?.addEventListener('input', function() {
  clearTimeout(debounceTimer);
  const q = this.value.trim();
  const div = document.getElementById('search-results');
  if (!q) { 
    div.innerHTML = ''; 
    div.style.display = 'none';
    return; 
  }
  debounceTimer = setTimeout(async () => {
    try {
      const res  = await fetch(`/api/search.php?q=${encodeURIComponent(q)}`);
      const data = await res.json();
      div.style.display = 'block';
      div.innerHTML = data.length
        ? data.map(i => `<div class="result-item" onclick="Cart.addItem(${i.id}, '${i.dish_name.replace(/'/g,"\\'")}', ${i.price}); document.getElementById('search-results').style.display='none'; document.getElementById('search-input').value='';">
             <strong>${i.dish_name}</strong> — ₹${i.price}
             <small>${i.tags || 'Standard'}</small>
           </div>`).join('')
        : '<div class="result-item">No results found</div>';
    } catch(e) {
      console.error(e);
    }
  }, 300);
});

// Close dropdown on outside click
document.addEventListener('click', (e) => {
    if (!e.target.closest('.smart-search')) {
        const div = document.getElementById('search-results');
        if(div) div.style.display = 'none';
    }
});

// Eco Mode Toggle
function toggleEcoMode() {
  const active = localStorage.getItem('eco_mode') === '1';
  localStorage.setItem('eco_mode', active ? '0' : '1');
  document.getElementById('eco-toggle').textContent = active ? '🌱 Eco: OFF' : '🌿 Eco: ON';
  document.body.classList.toggle('eco-active', !active);
  applyEcoFilter(!active);
}

function applyEcoFilter(active) {
  document.querySelectorAll('.restaurant-card').forEach(card => {
    const isEco = card.dataset.eco === '1';
    card.classList.toggle('eco-highlight', active && isEco);
    if (active && !isEco) card.style.opacity = '0.5';
    else card.style.opacity = '1';
  });
}

// On page load, restore preference
if (localStorage.getItem('eco_mode') === '1') {
  const btn = document.getElementById('eco-toggle');
  if(btn) btn.textContent = '🌿 Eco: ON';
  document.body.classList.add('eco-active');
  document.addEventListener('DOMContentLoaded', () => applyEcoFilter(true));
}


// Admin Alerts Polling
setInterval(async () => {
  try {
    const res  = await fetch('/api/alerts.php?action=list');
    const data = await res.json();
    const badge = document.getElementById('alert-badge');
    if (data.count > 0 && badge) {
      document.getElementById('alert-count').textContent = data.count;
      badge.style.display = 'inline-block';
    } else if (badge) {
      badge.style.display = 'none';
    }
  } catch(e) {
    console.error("Alerts polling failed", e);
  }
}, 60000); // check every minute
</script>
