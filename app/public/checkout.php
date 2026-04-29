<?php
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/../src/helpers/Security.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= Security::csrf_token() ?>">
  <title>Checkout — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
  <style>
    .checkout-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: var(--space-2xl); }
    @media (max-width: 900px) { .checkout-grid { grid-template-columns: 1fr; } }
    .checkout-item { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: var(--text-sm); }
    .checkout-total { display: flex; justify-content: space-between; font-weight: 700; font-size: var(--text-lg); color: var(--accent-amber); margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <section class="section-gap" style="margin-top: 60px;">
    <div class="container">
      <div class="section-header reveal">
        <h2>Checkout</h2>
        <p>Review your items and enter delivery details.</p>
      </div>

      <div class="checkout-grid">
        <!-- Form Section -->
        <div class="card reveal" style="padding: var(--space-xl); background: rgba(20,20,20,0.8);">
          <form id="checkoutForm">
            <h3 style="margin-bottom: var(--space-md);">Delivery Address</h3>
            <div class="form-group">
              <label class="form-label" for="address">Full Address</label>
              <textarea id="address" class="form-input" rows="3" required placeholder="123 Main St, Apartment 4B..."></textarea>
            </div>
            
            <div class="form-group">
              <label class="form-label" for="notes">Delivery Notes (Optional)</label>
              <input type="text" id="notes" class="form-input" placeholder="e.g. Leave at the door">
            </div>

            <h3 style="margin-top: var(--space-xl); margin-bottom: var(--space-md);">Payment Method</h3>
            <div class="form-group" style="display: flex; gap: var(--space-lg);">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="payment_method" value="cod" checked> Cash on Delivery
              </label>
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="radio" name="payment_method" value="online"> Pay Online
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn--lg" style="width: 100%; margin-top: var(--space-xl);" id="submitBtn">Place Order</button>
            <div id="checkoutError" style="color: #ef4444; margin-top: var(--space-sm); display: none;"></div>
          </form>
        </div>

        <!-- Summary Section -->
        <div class="card reveal" style="padding: var(--space-xl); background: var(--bg-card); height: fit-content;">
          <h3 style="margin-bottom: var(--space-md);">Order Summary</h3>
          <div id="orderSummaryList" style="margin-bottom: var(--space-md);">
            <!-- Populated via JS -->
          </div>
          <div class="checkout-item">
            <span>Subtotal</span>
            <span id="summarySubtotal">₹0.00</span>
          </div>
          <div class="checkout-item">
            <span>Delivery Fee</span>
            <span>₹40.00</span>
          </div>
          <div class="checkout-total">
            <span>Total</span>
            <span id="summaryTotal">₹0.00</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CART DRAWER -->
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>
  <div class="toast-container" id="toastContainer"></div>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Populate summary
      const items = JSON.parse(localStorage.getItem('feastly_cart')) || [];
      if (items.length === 0) {
        window.location.href = '/menu.php';
        return;
      }

      const list = document.getElementById('orderSummaryList');
      let subtotal = 0;
      
      list.innerHTML = items.map(i => {
        const lineTotal = i.price * i.quantity;
        subtotal += lineTotal;
        return `<div class="checkout-item"><span>${i.quantity}x ${i.name}</span><span>₹${lineTotal.toFixed(2)}</span></div>`;
      }).join('');

      document.getElementById('summarySubtotal').textContent = `₹${subtotal.toFixed(2)}`;
      document.getElementById('summaryTotal').textContent = `₹${(subtotal + 40).toFixed(2)}`;

      // Handle Submit
      document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('submitBtn');
        const errDiv = document.getElementById('checkoutError');
        
        btn.textContent = 'Processing...';
        btn.disabled = true;
        errDiv.style.display = 'none';

        const address = document.getElementById('address').value;
        const notes = document.getElementById('notes').value;
        const payment_method = document.querySelector('input[name="payment_method"]:checked').value;

        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          const res = await fetch('/api/place-order.php', {
            method: 'POST',
            headers: { 
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken 
            },
            body: JSON.stringify({ items, address, notes, payment_method })
          });
          const data = await res.json();
          
          if (data.success) {
            localStorage.removeItem('feastly_cart');
            window.location.href = '/order-confirmation.php?id=' + data.order_id;
          } else {
            throw new Error(data.error || 'Failed to place order');
          }
        } catch (err) {
          errDiv.textContent = err.message;
          errDiv.style.display = 'block';
          btn.textContent = 'Place Order';
          btn.disabled = false;
        }
      });
    });
  </script>
</body>
</html>
