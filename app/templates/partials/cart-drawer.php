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
    <div id="delivery-estimate" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 8px; border-radius: 6px; font-size: 0.85rem; text-align: center; margin-bottom: 12px; border: 1px solid rgba(16,185,129,0.2);">
      🕐 Calculating delivery estimate...
    </div>
    <div id="cost-breakdown" class="breakdown-card" style="display:none">
      <h4 style="margin-bottom:8px;">💰 Price Breakdown</h4>
      <table style="width:100%; text-align:left; font-size:0.9rem;">
        <tr><td>Food Subtotal</td>    <td id="bd-subtotal" style="text-align:right;"></td></tr>
        <tr><td>Delivery Fee</td>     <td id="bd-delivery" style="text-align:right;"></td></tr>
        <tr><td>GST (5%)</td>         <td id="bd-gst" style="text-align:right;"></td></tr>
        <tr><td>Platform Fee</td>     <td id="bd-platform" style="text-align:right;"></td></tr>
        <tr><td>Packing Charge</td>   <td id="bd-packing" style="text-align:right;"></td></tr>
        <tr class="total-row" style="border-top:1px solid var(--border); font-size:1rem; margin-top:8px;">
          <td><strong>Total</strong></td><td id="bd-total" style="text-align:right; font-weight:bold; color:var(--accent-amber);"></td>
        </tr>
      </table>
    </div>
    <a href="/checkout.php" class="btn btn-primary" style="width:100%; margin-top:var(--space-md);">
      Proceed to Checkout →
    </a>
  </div>
</aside>
