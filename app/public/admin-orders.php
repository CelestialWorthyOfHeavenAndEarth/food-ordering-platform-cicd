<?php
session_start(['cookie_secure'=>false,'cookie_httponly'=>true,'cookie_samesite'=>'Strict','use_strict_mode'=>true]);
if (!isset($_SESSION['user_id']) || ($_SESSION['role']??'') !== 'admin') {
    header('Location: /login.php'); exit;
}
$user_logged = true;
$is_admin = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Order Management — Feastly Admin</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .admin-topbar { background:linear-gradient(135deg,#0f172a,#1e1b4b); padding:2rem 0 1.5rem; border-bottom:1px solid rgba(139,92,246,.3); }
    .admin-topbar h1 { color:#a78bfa; font-size:1.8rem; margin:0 0 .25rem; }
    .admin-topbar p { color:var(--text-muted); margin:0; }
    .admin-nav { display:flex; gap:1rem; margin-top:1.25rem; flex-wrap:wrap; }
    .admin-nav a { color:#a78bfa; text-decoration:none; font-size:.9rem; padding:.4rem .9rem; border:1px solid rgba(139,92,246,.3); border-radius:20px; transition:all .2s; }
    .admin-nav a:hover, .admin-nav a.active { background:#a78bfa; color:#0f172a; font-weight:600; }

    /* Stats row */
    .stats-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:1rem; margin:2rem 0; }
    .stat-pill { background:var(--surface-light); border-radius:12px; padding:1.25rem; text-align:center; border:1px solid var(--border); }
    .stat-pill__val { font-size:2rem; font-weight:800; color:var(--accent-amber); }
    .stat-pill__lbl { font-size:.8rem; color:var(--text-muted); margin-top:.25rem; }

    /* Filter bar */
    .filter-bar { display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1.5rem; align-items:center; }
    .filter-btn { padding:.4rem 1rem; border-radius:20px; border:1px solid var(--border); background:var(--surface-light); color:var(--text-muted); cursor:pointer; font-size:.85rem; transition:all .2s; }
    .filter-btn.active, .filter-btn:hover { background:var(--accent-amber); color:#000; border-color:var(--accent-amber); font-weight:600; }

    /* Order cards */
    .orders-grid { display:flex; flex-direction:column; gap:1rem; }
    .order-card { background:var(--surface-light); border-radius:14px; border:1px solid var(--border); overflow:hidden; }
    .order-card__header { display:grid; grid-template-columns:auto 1fr auto; gap:1rem; align-items:center; padding:1.25rem 1.5rem; cursor:pointer; }
    .order-card__id { font-size:1.1rem; font-weight:700; color:var(--accent-amber); }
    .order-card__meta { font-size:.85rem; color:var(--text-muted); }
    .order-card__meta strong { color:var(--text); display:block; margin-bottom:.15rem; }
    .status-badge { display:inline-block; padding:.3rem .9rem; border-radius:20px; font-size:.8rem; font-weight:600; text-transform:capitalize; }
    .status-pending      { background:rgba(249,115,22,.15); color:#fb923c; }
    .status-confirmed    { background:rgba(59,130,246,.15); color:#60a5fa; }
    .status-preparing    { background:rgba(234,179,8,.15);  color:#facc15; }
    .status-cooking      { background:rgba(239,68,68,.15);  color:#f87171; }
    .status-out_for_delivery { background:rgba(139,92,246,.15); color:#a78bfa; }
    .status-delivered    { background:rgba(34,197,94,.15);  color:#4ade80; }
    .status-cancelled    { background:rgba(107,114,128,.15);color:#9ca3af; }

    .order-card__body { border-top:1px solid var(--border); padding:1.25rem 1.5rem; display:none; }
    .order-card__body.open { display:block; }
    .order-items-table { width:100%; border-collapse:collapse; margin-bottom:1rem; font-size:.9rem; }
    .order-items-table th { text-align:left; color:var(--text-muted); font-weight:500; padding:.4rem 0; border-bottom:1px solid var(--border); }
    .order-items-table td { padding:.5rem 0; border-bottom:1px solid rgba(255,255,255,.04); }
    .action-bar { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem; }
    .action-btn { padding:.45rem 1.1rem; border:none; border-radius:8px; font-size:.85rem; font-weight:600; cursor:pointer; transition:opacity .2s; }
    .action-btn:hover { opacity:.8; }
    .btn-confirm   { background:#3b82f6; color:#fff; }
    .btn-prepare   { background:#eab308; color:#000; }
    .btn-cook      { background:#ef4444; color:#fff; }
    .btn-deliver   { background:#8b5cf6; color:#fff; }
    .btn-done      { background:#22c55e; color:#fff; }
    .btn-cancel    { background:#6b7280; color:#fff; }
    .address-box { background:var(--surface); border-radius:8px; padding:.75rem 1rem; font-size:.85rem; color:var(--text-muted); margin-top:.75rem; }

    @media(max-width:600px){.order-card__header{grid-template-columns:1fr 1fr;}}
  </style>
</head>
<body>
<?php include __DIR__.'/../templates/layout/nav.php'; ?>

<div class="admin-topbar">
  <div class="container">
    <h1>📦 Order Management</h1>
    <p>Accept, track, and manage all customer orders in real-time.</p>
    <nav class="admin-nav">
      <a href="/admin-orders.php" class="active">📦 Orders</a>
      <a href="/admin-insights.php">📊 Analytics</a>
      <a href="/index.php">🏠 Back to Site</a>
    </nav>
  </div>
</div>

<div class="container" style="padding-top:0;">
  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-pill"><div class="stat-pill__val" id="s-total">—</div><div class="stat-pill__lbl">Total Orders</div></div>
    <div class="stat-pill"><div class="stat-pill__val" id="s-pending" style="color:#fb923c;">—</div><div class="stat-pill__lbl">Pending</div></div>
    <div class="stat-pill"><div class="stat-pill__val" id="s-active" style="color:#a78bfa;">—</div><div class="stat-pill__lbl">Active</div></div>
    <div class="stat-pill"><div class="stat-pill__val" id="s-delivered" style="color:#4ade80;">—</div><div class="stat-pill__lbl">Delivered</div></div>
    <div class="stat-pill"><div class="stat-pill__val" id="s-revenue">—</div><div class="stat-pill__lbl">Revenue (₹)</div></div>
  </div>

  <!-- Filter bar -->
  <div class="filter-bar">
    <span style="color:var(--text-muted); font-size:.9rem;">Filter:</span>
    <button class="filter-btn active" onclick="loadOrders('all', this)">All</button>
    <button class="filter-btn" onclick="loadOrders('pending', this)">⏳ Pending</button>
    <button class="filter-btn" onclick="loadOrders('confirmed', this)">✅ Confirmed</button>
    <button class="filter-btn" onclick="loadOrders('preparing', this)">🥘 Preparing</button>
    <button class="filter-btn" onclick="loadOrders('cooking', this)">🔥 Cooking</button>
    <button class="filter-btn" onclick="loadOrders('out_for_delivery', this)">🛵 On the Way</button>
    <button class="filter-btn" onclick="loadOrders('delivered', this)">🎉 Delivered</button>
    <button class="filter-btn" onclick="loadOrders('cancelled', this)">❌ Cancelled</button>
    <button class="filter-btn" onclick="loadOrders(currentFilter, null)" style="margin-left:auto; border-color:var(--accent-amber); color:var(--accent-amber);">🔄 Refresh</button>
  </div>

  <div class="orders-grid" id="orders-container">
    <p style="color:var(--text-muted); text-align:center; padding:3rem;">Loading orders…</p>
  </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="/assets/js/main.js"></script>

<script>
let currentFilter = 'all';

const STATUS_FLOW = {
  pending:          { next: 'confirmed',        label: '✅ Accept Order',      cls: 'btn-confirm'  },
  confirmed:        { next: 'preparing',         label: '🥘 Start Preparing',   cls: 'btn-prepare'  },
  preparing:        { next: 'cooking',           label: '🔥 Cooking',           cls: 'btn-cook'     },
  cooking:          { next: 'out_for_delivery',  label: '🛵 Out for Delivery',   cls: 'btn-deliver'  },
  out_for_delivery: { next: 'delivered',         label: '🎉 Mark Delivered',    cls: 'btn-done'     },
};

function statusBadge(s) {
  return `<span class="status-badge status-${s}">${s.replace(/_/g,' ')}</span>`;
}

async function updateStatus(orderId, newStatus, card) {
  const res = await fetch('/api/admin-orders.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ order_id: orderId, status: newStatus })
  });
  const data = await res.json();
  if (data.success) {
    showToast(`Order #${orderId} → ${newStatus.replace(/_/g,' ')}`);
    loadOrders(currentFilter, null);
  } else {
    showToast('Error: ' + (data.error || 'Unknown'), 'error');
  }
}

function showToast(msg, type='success') {
  const t = document.createElement('div');
  t.className = 'toast toast--' + type;
  t.textContent = msg;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

async function loadOrders(filter = 'all', btnEl) {
  currentFilter = filter;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  if (btnEl) btnEl.classList.add('active');

  const res  = await fetch(`/api/admin-orders.php?status=${filter}`);
  const data = await res.json();
  const orders = data.orders || [];

  // Stats
  document.getElementById('s-total').textContent = orders.length;
  document.getElementById('s-pending').textContent   = orders.filter(o=>o.status==='pending').length;
  document.getElementById('s-active').textContent    = orders.filter(o=>['confirmed','preparing','cooking','out_for_delivery'].includes(o.status)).length;
  document.getElementById('s-delivered').textContent = orders.filter(o=>o.status==='delivered').length;
  const revenue = orders.filter(o=>o.status==='delivered').reduce((a,o)=>a+parseFloat(o.total_amount),0);
  document.getElementById('s-revenue').textContent   = '₹' + Math.round(revenue).toLocaleString('en-IN');

  const container = document.getElementById('orders-container');
  if (!orders.length) {
    container.innerHTML = '<p style="color:var(--text-muted);text-align:center;padding:3rem;">No orders found.</p>';
    return;
  }

  container.innerHTML = orders.map(o => {
    const flow = STATUS_FLOW[o.status];
    const cancelBtn = !['delivered','cancelled'].includes(o.status)
      ? `<button class="action-btn btn-cancel" onclick="updateStatus(${o.id},'cancelled')">❌ Cancel</button>` : '';
    const nextBtn = flow
      ? `<button class="action-btn ${flow.cls}" onclick="updateStatus(${o.id},'${flow.next}')">${flow.label}</button>` : '';

    const itemRows = (o.items||[]).map(i =>
      `<tr><td>${i.name}</td><td>×${i.quantity}</td><td style="color:var(--accent-amber);">₹${(i.unit_price*i.quantity).toFixed(0)}</td></tr>`
    ).join('');

    return `
    <div class="order-card" id="order-card-${o.id}">
      <div class="order-card__header" onclick="toggleCard(${o.id})">
        <div>
          <div class="order-card__id">#${o.id}</div>
          <div style="font-size:.75rem;color:var(--text-muted);">${o.created_at.slice(0,16)}</div>
        </div>
        <div class="order-card__meta">
          <strong>${o.customer_name}</strong>
          ${o.customer_email}
        </div>
        <div style="text-align:right;">
          ${statusBadge(o.status)}
          <div style="color:var(--accent-amber); font-weight:700; margin-top:.35rem;">₹${parseFloat(o.total_amount).toFixed(0)}</div>
        </div>
      </div>
      <div class="order-card__body" id="body-${o.id}">
        <table class="order-items-table">
          <thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
          <tbody>${itemRows || '<tr><td colspan="3" style="color:var(--text-muted)">No items</td></tr>'}</tbody>
        </table>
        <div class="address-box">📍 ${o.delivery_address}${o.notes ? '<br>📝 ' + o.notes : ''}</div>
        <div style="font-size:.8rem; color:var(--text-muted); margin-top:.5rem;">Payment: ${o.payment_method.toUpperCase()}</div>
        <div class="action-bar">
          ${nextBtn}
          ${cancelBtn}
        </div>
      </div>
    </div>`;
  }).join('');
}

function toggleCard(id) {
  const body = document.getElementById(`body-${id}`);
  body.classList.toggle('open');
}

loadOrders('all', document.querySelector('.filter-btn.active'));
setInterval(() => loadOrders(currentFilter, null), 30000); // auto-refresh every 30s
</script>
</body>
</html>
