<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/../src/controllers/AuthController.php';
AuthController::requireAdmin();

$user_logged = true;
$is_admin = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restaurant Management — Feastly Admin</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <style>
    .admin-topbar { background:linear-gradient(135deg,#0f172a,#1e1b4b); padding:2rem 0 1.5rem; border-bottom:1px solid rgba(139,92,246,.3); }
    .admin-topbar h1 { color:#a78bfa; font-size:1.8rem; margin:0 0 .25rem; }
    .admin-topbar p { color:var(--text-muted); margin:0; }
    .admin-nav { display:flex; gap:1rem; margin-top:1.25rem; flex-wrap:wrap; }
    .admin-nav a { color:#a78bfa; text-decoration:none; font-size:.9rem; padding:.4rem .9rem; border:1px solid rgba(139,92,246,.3); border-radius:20px; transition:all .2s; }
    .admin-nav a:hover, .admin-nav a.active { background:#a78bfa; color:#0f172a; font-weight:600; }

    .grid-container { display: grid; grid-template-columns: 1fr; gap: 2rem; margin-top: 2rem; }
    @media(min-width: 900px) { .grid-container { grid-template-columns: 350px 1fr; } }
    
    .panel { background: var(--surface-light); border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; }
    .panel h2 { font-size: 1.2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; }

    .list-item { padding: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: background 0.2s; }
    .list-item:hover, .list-item.active { background: rgba(139,92,246,0.1); border-left: 3px solid #a78bfa; }
    
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--border); }
    th { color: var(--text-muted); font-size: 0.85rem; font-weight: 500; }
    tr:hover { background: rgba(255,255,255,0.02); }

    .toggle-switch { position: relative; display: inline-block; width: 40px; height: 20px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #4b5563; transition: .2s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .2s; border-radius: 50%; }
    input:checked + .slider { background-color: var(--accent-amber); }
    input:checked + .slider:before { transform: translateX(20px); }

    .input-edit { background: transparent; border: 1px solid transparent; color: var(--text); padding: 4px; width: 80px; border-radius: 4px; font-family: inherit; }
    .input-edit:focus { border-color: var(--accent-amber); outline: none; background: rgba(0,0,0,0.2); }
    
    .save-btn { padding: 4px 12px; font-size: 0.8rem; background: var(--accent-amber); color: #000; border: none; border-radius: 4px; cursor: pointer; display: none; }
    .save-btn.show { display: inline-block; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../templates/layout/nav.php'; ?>

<div class="admin-topbar">
  <div class="container">
    <h1>🏪 Restaurant Management</h1>
    <p>Manage restaurant settings, toggle availability, and update menu pricing.</p>
    <nav class="admin-nav">
      <a href="/admin-orders.php">📦 Orders</a>
      <a href="/admin-restaurants.php" class="active">🏪 Restaurants</a>
      <a href="/admin-insights.php">📊 Analytics</a>
    </nav>
  </div>
</div>

<div class="container grid-container">
  
  <!-- Restaurants List -->
  <div class="panel">
    <h2>Restaurants</h2>
    <div id="restaurant-list">
      <p style="color:var(--text-muted);">Loading...</p>
    </div>
  </div>

  <!-- Menu & Details Panel -->
  <div class="panel" id="details-panel" style="display:none;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 1.5rem;">
      <h2 id="r-name" style="margin:0; border:none; padding:0; font-size:1.5rem;">Restaurant Name</h2>
      <div>
        <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem;">
          Active
          <label class="toggle-switch">
            <input type="checkbox" id="r-active" onchange="updateRestaurant()">
            <span class="slider"></span>
          </label>
        </label>
      </div>
    </div>
    
    <div style="display:flex; gap:2rem; margin-bottom: 2rem; padding: 1rem; background:rgba(0,0,0,0.2); border-radius:8px;">
      <div>
        <span style="color:var(--text-muted); font-size:0.85rem; display:block;">Eco-Friendly</span>
        <label class="toggle-switch" style="margin-top:4px;">
          <input type="checkbox" id="r-eco" onchange="updateRestaurant()">
          <span class="slider"></span>
        </label>
      </div>
      <div>
        <span style="color:var(--text-muted); font-size:0.85rem; display:block;">Eco Score (0-100)</span>
        <input type="number" id="r-eco-score" class="input-edit" style="border:1px solid var(--border); margin-top:4px;" onchange="updateRestaurant()">
      </div>
      <div>
        <span style="color:var(--text-muted); font-size:0.85rem; display:block;">Avg Delivery (mins)</span>
        <input type="number" id="r-delivery" class="input-edit" style="border:1px solid var(--border); margin-top:4px;" onchange="updateRestaurant()">
      </div>
    </div>

    <h3>Menu Items</h3>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Category</th>
            <th>Price (₹)</th>
            <th>Available</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="menu-list"></tbody>
      </table>
    </div>
  </div>

</div>

<div class="toast-container" id="toastContainer"></div>
<script src="/assets/js/main.js"></script>
<script>
let currentRestaurantId = null;

function showToast(msg, type='success') {
  const t = document.createElement('div');
  t.className = 'toast toast--' + type;
  t.textContent = msg;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

async function fetchRestaurants() {
  const res = await fetch('/api/admin-restaurants.php?action=list');
  const data = await res.json();
  const list = document.getElementById('restaurant-list');
  
  if (!data.length) {
    list.innerHTML = '<p>No restaurants found.</p>';
    return;
  }

  list.innerHTML = data.map(r => `
    <div class="list-item ${r.id === currentRestaurantId ? 'active' : ''}" onclick="loadRestaurant(${r.id})">
      <div>
        <strong style="display:block;">${r.name}</strong>
        <small style="color:var(--text-muted);">★ ${r.avg_rating} | ${r.is_active ? '<span style="color:#4ade80">Active</span>' : '<span style="color:#f87171">Offline</span>'}</small>
      </div>
      <span>›</span>
    </div>
  `).join('');
}

async function loadRestaurant(id) {
  currentRestaurantId = id;
  document.getElementById('details-panel').style.display = 'block';
  fetchRestaurants(); // refresh list to show active state

  const res = await fetch(`/api/admin-restaurants.php?action=get&id=${id}`);
  const data = await res.json();
  
  document.getElementById('r-name').textContent = data.restaurant.name;
  document.getElementById('r-active').checked = !!data.restaurant.is_active;
  document.getElementById('r-eco').checked = !!data.restaurant.is_eco_friendly;
  document.getElementById('r-eco-score').value = data.restaurant.eco_score;
  document.getElementById('r-delivery').value = data.restaurant.avg_delivery_time;

  const tbody = document.getElementById('menu-list');
  tbody.innerHTML = data.menu.map(m => `
    <tr>
      <td><strong>${m.name}</strong><br><small style="color:var(--text-muted)">${m.is_veg ? 'Veg' : 'Non-Veg'}</small></td>
      <td>${m.category}</td>
      <td>
        <input type="number" class="input-edit" id="price-${m.id}" value="${parseFloat(m.price)}" oninput="document.getElementById('btn-${m.id}').classList.add('show')">
      </td>
      <td>
        <label class="toggle-switch">
          <input type="checkbox" ${m.is_available ? 'checked' : ''} onchange="updateMenuStatus(${m.id}, this.checked)">
          <span class="slider"></span>
        </label>
      </td>
      <td>
        <button id="btn-${m.id}" class="save-btn" onclick="savePrice(${m.id})">Save</button>
      </td>
    </tr>
  `).join('');
}

async function updateRestaurant() {
  if (!currentRestaurantId) return;
  const payload = {
    action: 'update_restaurant',
    id: currentRestaurantId,
    is_active: document.getElementById('r-active').checked ? 1 : 0,
    is_eco_friendly: document.getElementById('r-eco').checked ? 1 : 0,
    eco_score: document.getElementById('r-eco-score').value,
    avg_delivery_time: document.getElementById('r-delivery').value
  };

  const res = await fetch('/api/admin-restaurants.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
  const data = await res.json();
  if(data.success) {
    showToast('Restaurant settings updated');
    fetchRestaurants();
  } else {
    showToast('Failed to update', 'error');
  }
}

async function updateMenuStatus(menuId, isAvailable) {
  const payload = {
    action: 'update_menu_status',
    id: menuId,
    is_available: isAvailable ? 1 : 0
  };
  const res = await fetch('/api/admin-restaurants.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
  });
  if((await res.json()).success) showToast('Menu status updated');
}

async function savePrice(menuId) {
  const price = document.getElementById(`price-${menuId}`).value;
  const payload = { action: 'update_menu_price', id: menuId, price: price };
  
  const res = await fetch('/api/admin-restaurants.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
  });
  if((await res.json()).success) {
    showToast('Price updated successfully');
    document.getElementById(`btn-${menuId}`).classList.remove('show');
  }
}

fetchRestaurants();
</script>
</body>
</html>
