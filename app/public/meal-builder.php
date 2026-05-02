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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Build Your Meal — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <style>
    .meal-builder { padding: 4rem 2rem; max-width: 1200px; margin: 0 auto; }
    .item-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .item-card { background: var(--surface-light); border-radius: 8px; padding: 1rem; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; text-align: center; }
    .item-card:hover { transform: translateY(-3px); }
    .item-card input { display: none; }
    .item-card.selected { border-color: var(--accent-amber); background: rgba(245, 158, 11, 0.1); }
    .item-card img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem; }
    
    .summary-bar { position: fixed; bottom: 0; left: 0; right: 0; background: var(--surface); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 -4px 12px rgba(0,0,0,0.5); z-index: 100; border-top: 1px solid var(--border); }
    .summary-bar span { font-size: 1.1rem; }
    .discount-tag { color: var(--accent-amber); font-weight: bold; }
    @media(max-width: 768px) {
        .summary-bar { flex-direction: column; gap: 10px; }
        .summary-bar button { width: 100%; }
    }
  </style>
</head>
<body data-user-id="<?= $_SESSION['user_id'] ?>">
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <div class="meal-builder section-gap" style="margin-bottom: 80px;">
    <h2>🍱 Build Your Meal</h2>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Select 2 or more items to unlock combo discounts! 2 items = 5% off, 3 items = 10% off, 4+ items = 15% off.</p>

    <div id="categories-container"></div>
  </div>

  <div class="summary-bar">
    <div>
        <span style="margin-right:1rem;">Selected: <strong id="item-count">0</strong> items</span>
        <span style="margin-right:1rem; color:var(--text-muted); text-decoration:line-through;">Base: <strong id="base-price">₹0</strong></span>
        <span class="discount-tag" style="margin-right:1rem;">Discount: <strong id="discount-pct">0%</strong></span>
        <span>Total: <strong id="final-price" style="font-size:1.3rem;">₹0</strong></span>
    </div>
    <button id="build-btn" class="btn btn-primary" onclick="submitCombo()" disabled>Add Combo to Cart</button>
  </div>

  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>

  <script src="/assets/js/cart.js"></script>
  <script>
  let selectedIds = [];
  const discountRules = { 2: 5, 3: 10, 4: 15 };

  async function loadItems() {
    const res  = await fetch('/api/meal-builder.php');
    const data = await res.json();
    const cont = document.getElementById('categories-container');
    for (const [cat, items] of Object.entries(data)) {
      cont.innerHTML += `<h3>${cat}</h3><div class="item-grid">
        ${items.map(i => `
          <label class="item-card" id="card-${i.id}">
            <input type="checkbox" value="${i.id}" data-price="${i.price}" data-name="${i.dish_name.replace(/'/g,"\\'")}" onchange="toggleItem(this)">
            <img src="${i.image_url || '/assets/images/default.png'}" alt="${i.dish_name}">
            <strong style="margin-bottom: 5px;">${i.dish_name}</strong>
            <span style="color: var(--accent-amber);">₹${i.price}</span>
          </label>`).join('')}
      </div>`;
    }
  }

  function toggleItem(el) {
    const card = document.getElementById(`card-${el.value}`);
    if (el.checked) {
      selectedIds.push({ id: +el.value, price: +el.dataset.price, name: el.dataset.name });
      card.classList.add('selected');
    } else {
      selectedIds = selectedIds.filter(i => i.id !== +el.value);
      card.classList.remove('selected');
    }
    updateSummary();
  }

  function updateSummary() {
    const count    = selectedIds.length;
    const base     = selectedIds.reduce((s, i) => s + i.price, 0);
    const discount = discountRules[Math.min(count, 4)] || 0;
    const final    = base * (1 - discount / 100);
    
    document.getElementById('item-count').textContent  = count;
    document.getElementById('base-price').textContent  = '₹' + base.toFixed(2);
    document.getElementById('discount-pct').textContent = discount + '%';
    document.getElementById('final-price').textContent = '₹' + final.toFixed(2);
    
    document.getElementById('build-btn').disabled = count < 2;
  }

  async function submitCombo() {
    const userId = document.body.dataset.userId;
    const res = await fetch('/api/meal-builder.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, item_ids: selectedIds.map(i => i.id) })
    });
    const data = await res.json();
    if (data.combo_id) {
        // Add all items to cart. We will add them at their discounted proportional price or as a single "Combo" item.
        // For simplicity, we just add a single combo item to the cart.
        Cart.addItem('combo_'+data.combo_id, 'Meal Combo #' + data.combo_id, data.final_price);
    }
  }

  loadItems();
  </script>
</body>
</html>
