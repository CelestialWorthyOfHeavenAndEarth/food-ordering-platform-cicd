const Cart = (() => {
  let items = JSON.parse(localStorage.getItem('feastly_cart') || '[]');

  const DELIVERY_FEE = 40;

  function save() {
    localStorage.setItem('feastly_cart', JSON.stringify(items));
    updateBadge();
    render();
  }

  function addItem(id, name, price) {
    const existing = items.find(i => i.id == id);
    if (existing) {
      existing.quantity++;
    } else {
      items.push({ id, name, price: parseFloat(price), quantity: 1 });
    }
    save();
    Toast.show(`${name} added to cart`, 'success');
    openDrawer();
    document.getElementById(`qty-${id}`)?.textContent !== undefined &&
      (document.getElementById(`qty-${id}`).textContent = getQty(id));
  }

  function decrease(id) {
    const idx = items.findIndex(i => i.id == id);
    if (idx === -1) return;
    items[idx].quantity--;
    if (items[idx].quantity <= 0) items.splice(idx, 1);
    save();
    const qtyEl = document.getElementById(`qty-${id}`);
    if (qtyEl) qtyEl.textContent = getQty(id);
  }

  function remove(id) {
    items = items.filter(i => i.id != id);
    save();
  }

  function getQty(id) {
    return items.find(i => i.id == id)?.quantity || 0;
  }

  function getSubtotal() {
    return items.reduce((s, i) => s + i.price * i.quantity, 0);
  }

  function updateBadge() {
    const total = items.reduce((s, i) => s + i.quantity, 0);
    const badge = document.querySelector('.nav__cart-badge');
    const btn   = document.getElementById('cartToggle');
    if (total > 0) {
      if (!badge) {
        const b = document.createElement('span');
        b.className = 'nav__cart-badge';
        b.textContent = total;
        btn?.appendChild(b);
      } else {
        badge.textContent = total;
        badge.style.animation = 'none';
        requestAnimationFrame(() => badge.style.animation = 'cartBounce 0.4s ease');
      }
    } else {
      badge?.remove();
    }
  }

  function render() {
    const container = document.getElementById('cartItems');
    const footer    = document.getElementById('cartFooter');
    const emptyEl   = document.getElementById('cartEmpty');
    if (!container) return;

    if (items.length === 0) {
      emptyEl && (emptyEl.style.display = 'flex');
      footer  && (footer.style.display = 'none');
      return;
    }

    emptyEl && (emptyEl.style.display = 'none');
    footer  && (footer.style.display = 'block');

    const subtotal = getSubtotal();
    loadBreakdown(subtotal, 2); // Default 2km for preview

    const html = items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <div class="cart-item__info">
          <strong>${item.name}</strong>
          <span class="cart-item__price">₹${item.price.toFixed(2)}</span>
        </div>
        <div class="cart-item__controls">
          <button class="qty-btn" onclick="Cart.decrease(${item.id})">−</button>
          <span class="qty-val">${item.quantity}</span>
          <button class="qty-btn qty-btn--add" onclick="Cart.addItem(${item.id}, '${item.name.replace(/'/g,"\\'")}', ${item.price})">+</button>
          <button class="qty-btn btn-danger" onclick="Cart.remove(${item.id})" style="margin-left:4px;">🗑</button>
        </div>
        <div class="cart-item__line-total">₹${(item.price * item.quantity).toFixed(2)}</div>
      </div>
    `).join('');

    // Replace only items, keep empty div
    container.innerHTML = `<div id="cartEmpty" class="cart-empty" style="display:none;"></div>${html}`;
  }

  function openDrawer() {
    document.getElementById('cartDrawer')?.classList.add('open');
    document.getElementById('cartOverlay')?.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    document.getElementById('cartDrawer')?.classList.remove('open');
    document.getElementById('cartOverlay')?.classList.remove('open');
    document.body.style.overflow = '';
  }

  // Persist cart to server session on checkout
  async function syncToServer() {
    await fetch('/api/cart/sync.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items })
    });
  }

  async function loadBreakdown(subtotal, distanceKm) {
    if (subtotal <= 0) return;
    try {
      const res  = await fetch(`/api/cost-breakdown.php?subtotal=${subtotal}&distance_km=${distanceKm}`);
      const data = await res.json();
      document.getElementById('bd-subtotal').textContent  = '₹' + data.subtotal.toFixed(2);
      document.getElementById('bd-delivery').textContent  = '₹' + data.delivery_fee.toFixed(2);
      document.getElementById('bd-gst').textContent       = '₹' + data.gst.toFixed(2);
      document.getElementById('bd-platform').textContent  = '₹' + data.platform_fee.toFixed(2);
      document.getElementById('bd-packing').textContent   = '₹' + data.packing_charge.toFixed(2);
      document.getElementById('bd-total').textContent     = '₹' + data.total.toFixed(2);
      document.getElementById('cost-breakdown').style.display = 'block';
    } catch (e) {
      console.error("Failed to load cost breakdown", e);
    }
  }

  async function showDeliveryEstimate(restaurantId = 1, distanceKm = 2) {
    try {
      const res  = await fetch(`/api/predict-time.php?restaurant_id=${restaurantId}&distance_km=${distanceKm}`);
      const data = await res.json();
      const el = document.getElementById('delivery-estimate');
      if(el) el.textContent = `🕐 Estimated Delivery: ${data.range}`;
    } catch(e) {
      console.error(e);
    }
  }

  // Init
  updateBadge();
  render();
  showDeliveryEstimate();

  return { addItem, decrease, remove, openDrawer, closeDrawer, getItems: () => items, syncToServer, loadBreakdown, showDeliveryEstimate };
})();

// Cart drawer toggle
document.getElementById('cartToggle')?.addEventListener('click', Cart.openDrawer);
