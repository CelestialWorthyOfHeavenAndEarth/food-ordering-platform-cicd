<!-- Overlay -->
<div class="cart-overlay" id="cartOverlay" onclick="Cart.closeDrawer()"></div>

<!-- Cart Drawer -->
<aside class="cart-drawer" id="cartDrawer">
  <div class="cart-drawer__header">
    <h3>Your Order</h3>
    <button class="btn btn-ghost btn--icon" onclick="Cart.closeDrawer()">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>
  </div>
  <div class="cart-drawer__body" id="cartItems">
    <div class="cart-empty" id="cartEmpty">
      <div class="cart-empty__icon" style="color: var(--accent-amber);"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>
      <p>Your cart is empty</p>
      <small>Add items from the menu to get started</small>
      <a href="/menu.php" class="btn btn-primary" style="margin-top: var(--space-lg);">Browse Menu</a>
    </div>
  </div>
  <div class="cart-drawer__footer" id="cartFooter" style="display:none;">
    <div class="cart-total">
      <span>Subtotal</span>
      <strong id="cartSubtotal">₹0.00</strong>
    </div>
    <div class="cart-total">
      <span>Delivery Fee</span>
      <strong>₹40.00</strong>
    </div>
    <div class="divider--amber divider"></div>
    <div class="cart-total cart-total--grand">
      <span>Total</span>
      <strong id="cartTotal">₹0.00</strong>
    </div>
    <a href="/checkout.php" class="btn btn-primary" style="width:100%; margin-top:var(--space-md);">
      Proceed to Checkout →
    </a>
  </div>
</aside>
