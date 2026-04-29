<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Cart — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
  <style>
    .cart-page-grid {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: var(--space-2xl);
    }
    @media (max-width: 900px) {
      .cart-page-grid { grid-template-columns: 1fr; }
    }
    .cart-page-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: var(--space-lg);
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      margin-bottom: var(--space-md);
    }
    .cart-page-item__info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .cart-page-item__name {
      font-weight: 600;
      font-size: var(--text-lg);
      color: var(--text-primary);
    }
    .cart-page-item__price {
      color: var(--accent-amber);
      font-weight: 500;
    }
    .cart-page-controls {
      display: flex;
      align-items: center;
      gap: var(--space-md);
    }
    .cart-page-summary {
      background: var(--bg-card);
      padding: var(--space-xl);
      border-radius: var(--radius-lg);
      position: sticky;
      top: 100px;
    }
    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: var(--space-md);
      font-size: var(--text-md);
    }
    .summary-total {
      display: flex;
      justify-content: space-between;
      margin-top: var(--space-lg);
      padding-top: var(--space-lg);
      border-top: 1px solid var(--border);
      font-size: var(--text-xl);
      font-weight: 700;
      color: var(--accent-amber);
    }
  </style>
</head>
<body style="display: flex; flex-direction: column; min-height: 100vh;">
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <section class="section-gap" style="margin-top: 60px; flex: 1;">
    <div class="container">
      <div class="section-header reveal text-center" style="margin-bottom: var(--space-2xl);">
        <h2>Review Your Order</h2>
        <p>Make sure you have everything you need before proceeding to checkout.</p>
      </div>

      <div class="cart-page-grid reveal">
        <!-- Items List -->
        <div id="fullCartItems">
          <!-- Populated by JS -->
        </div>

        <!-- Summary -->
        <div>
          <div class="cart-page-summary">
            <h3 style="margin-bottom: var(--space-lg);">Order Summary</h3>
            <div class="summary-row">
              <span style="color: var(--text-secondary);">Subtotal</span>
              <strong id="fullCartSubtotal">₹0.00</strong>
            </div>
            <div class="summary-row">
              <span style="color: var(--text-secondary);">Delivery Fee</span>
              <strong>₹40.00</strong>
            </div>
            <div class="summary-total">
              <span>Total</span>
              <span id="fullCartTotal">₹0.00</span>
            </div>
            <a href="/checkout.php" class="btn btn-primary btn--lg" style="width: 100%; margin-top: var(--space-xl);" id="checkoutBtn">
              Proceed to Checkout →
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include __DIR__ . '/../templates/layout/footer.php'; ?>
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      function renderFullCart() {
        const items = JSON.parse(localStorage.getItem('feastly_cart')) || [];
        const container = document.getElementById('fullCartItems');
        const subtotalEl = document.getElementById('fullCartSubtotal');
        const totalEl = document.getElementById('fullCartTotal');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (items.length === 0) {
          container.innerHTML = `
            <div class="text-center" style="padding: var(--space-3xl); background: var(--bg-elevated); border-radius: var(--radius-md);">
              <div style="font-size: 3rem; margin-bottom: var(--space-md);">🛒</div>
              <h3 style="margin-bottom: var(--space-sm);">Your cart is empty</h3>
              <p style="color: var(--text-muted); margin-bottom: var(--space-lg);">Looks like you haven't added any items yet.</p>
              <a href="/menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
          `;
          subtotalEl.textContent = '₹0.00';
          totalEl.textContent = '₹0.00';
          checkoutBtn.classList.add('disabled');
          checkoutBtn.href = '#';
          return;
        }

        checkoutBtn.classList.remove('disabled');
        checkoutBtn.href = '/checkout.php';

        let subtotal = 0;
        container.innerHTML = items.map(item => {
          subtotal += item.price * item.quantity;
          return `
            <div class="cart-page-item">
              <div class="cart-page-item__info">
                <span class="cart-page-item__name">${item.name}</span>
                <span class="cart-page-item__price">₹${item.price.toFixed(2)}</span>
              </div>
              <div class="cart-page-controls">
                <div class="menu-card__qty" style="background: var(--bg-body); padding: 4px; border-radius: 20px;">
                  <button class="qty-btn" onclick="Cart.decrease(${item.id}); setTimeout(renderFullCart, 50)">−</button>
                  <span class="qty-val">${item.quantity}</span>
                  <button class="qty-btn qty-btn--add" onclick="Cart.addItem(${item.id}, '${item.name.replace(/'/g,"\\'")}', ${item.price}); setTimeout(renderFullCart, 50)">+</button>
                </div>
                <button class="btn btn-ghost" style="color: #ef4444; padding: 8px;" onclick="Cart.remove(${item.id}); setTimeout(renderFullCart, 50)">
                  🗑
                </button>
              </div>
            </div>
          `;
        }).join('');

        subtotalEl.textContent = `₹${subtotal.toFixed(2)}`;
        totalEl.textContent = `₹${(subtotal + 40).toFixed(2)}`;
      }

      // Initial render
      renderFullCart();

      // Listen for custom cart events if needed, but polling/re-rendering on click is fine for now
    });
  </script>
</body>
</html>
