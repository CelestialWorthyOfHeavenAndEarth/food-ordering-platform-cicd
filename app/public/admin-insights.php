<?php
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
$user_logged = isset($_SESSION['user_id']);
$is_admin    = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
// Optionally gate admin access
// if (!$is_admin) { header('Location: /index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Insights — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .admin-hero { background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); padding: 3rem 0 2rem; border-bottom: 1px solid rgba(139,92,246,0.3); }
    .admin-hero h1 { color: #a78bfa; font-size: 2rem; margin-bottom: 0.5rem; }
    .admin-hero p { color: var(--text-muted); }
    .insights-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin: 2rem 0; }
    .stat-card { background: var(--surface-light); border-radius: 16px; padding: 1.5rem; text-align: center; border: 1px solid var(--border); }
    .stat-card__value { font-size: 2.5rem; font-weight: 800; color: var(--accent-amber); }
    .stat-card__label { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem; }
    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 2rem 0; }
    .chart-box { background: var(--surface-light); border-radius: 16px; padding: 1.5rem; border: 1px solid var(--border); }
    .chart-box h3 { color: var(--text-muted); font-size: 1rem; margin-bottom: 1rem; }
    canvas { max-height: 280px; }
    .alert-section { background: var(--surface-light); border-radius: 16px; padding: 1.5rem; border: 1px solid var(--border); margin: 2rem 0; }
    .alert-section h3 { color: #f87171; margin-bottom: 1rem; }
    .alert-item { background: rgba(239,68,68,0.08); border-left: 3px solid #ef4444; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin-bottom: 0.75rem; font-size: 0.9rem; }
    .insight-box { background: rgba(167,139,250,0.1); border-left: 3px solid #a78bfa; padding: 0.75rem 1rem; border-radius: 0 8px 8px 0; margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-muted); }
    @media(max-width:768px){ .charts-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <div class="admin-hero">
    <div class="container">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
          <h1>📊 Admin Intelligence Dashboard</h1>
          <p>AI-powered insights, real-time alerts, and performance analytics for Feastly.</p>
        </div>
        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
          <a href="/index.php" class="btn btn-secondary">← Back to Store</a>
          <a href="/order-track.php" class="btn btn-primary">Order Management</a>
        </div>
      </div>
    </div>
  </div>

  <div class="container" style="padding-top: 0;">

    <!-- KPI Stats -->
    <div class="insights-grid">
      <div class="stat-card"><div class="stat-card__value" id="stat-orders">—</div><div class="stat-card__label">Total Orders</div></div>
      <div class="stat-card"><div class="stat-card__value" id="stat-revenue">—</div><div class="stat-card__label">Total Revenue (₹)</div></div>
      <div class="stat-card"><div class="stat-card__value" id="stat-users">—</div><div class="stat-card__label">Registered Customers</div></div>
      <div class="stat-card"><div class="stat-card__value" id="stat-alerts">—</div><div class="stat-card__label">Active Alerts</div></div>
    </div>

    <!-- AI Insight Messages -->
    <div id="ai-insights" style="margin-bottom: 1.5rem;"></div>

    <!-- Charts -->
    <div class="charts-grid">
      <div class="chart-box"><h3>Top Dishes (Last 30 Days)</h3><canvas id="topItemsChart"></canvas></div>
      <div class="chart-box"><h3>Peak Ordering Hours</h3><canvas id="peakHoursChart"></canvas></div>
      <div class="chart-box"><h3>Revenue Trend (Last 90 Days)</h3><canvas id="revenueChart"></canvas></div>
      <div class="chart-box"><h3>Delivery Performance by Restaurant</h3><canvas id="deliveryChart"></canvas></div>
    </div>

    <!-- Alerts -->
    <div class="alert-section">
      <h3>🔔 System Alerts</h3>
      <div id="alerts-container"><p style="color:var(--text-muted);">Loading alerts…</p></div>
    </div>

  </div>

  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>
  <div class="toast-container" id="toastContainer"></div>
  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>

  <script>
  const chartOpts = {
    responsive: true,
    plugins: { legend: { labels: { color: '#9ca3af' } } },
    scales: { x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } }, y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } } }
  };

  fetch('/api/insights.php').then(r => r.json()).then(data => {
    // AI Insights
    const aiBox = document.getElementById('ai-insights');
    if (data.ai_insights?.length) {
      data.ai_insights.forEach(msg => {
        aiBox.innerHTML += `<div class="insight-box">💡 ${msg}</div>`;
      });
    }

    // Stats
    if (data.top_items) {
      const totalOrders = data.top_items.reduce((a, i) => a + parseInt(i.order_count||0), 0);
      document.getElementById('stat-orders').textContent = totalOrders || '—';
    }
    if (data.revenue_trend?.length) {
      const total = data.revenue_trend.reduce((a, r) => a + parseFloat(r.revenue||0), 0);
      document.getElementById('stat-revenue').textContent = total > 0 ? '₹' + Math.round(total).toLocaleString('en-IN') : '—';
    }
    if (data.user_count !== undefined) {
      document.getElementById('stat-users').textContent = data.user_count;
    }

    // Charts
    if (data.top_items?.length) {
      new Chart(document.getElementById('topItemsChart'), {
        type: 'bar',
        data: { labels: data.top_items.map(i => i.dish_name), datasets: [{ label: 'Orders', data: data.top_items.map(i => i.order_count), backgroundColor: '#e94560' }] },
        options: chartOpts
      });
    }
    if (data.peak_hours?.length) {
      new Chart(document.getElementById('peakHoursChart'), {
        type: 'line',
        data: { labels: data.peak_hours.map(h => `${h.hour}:00`), datasets: [{ label: 'Orders', data: data.peak_hours.map(h => h.order_count), borderColor: '#a78bfa', backgroundColor: 'rgba(167,139,250,0.1)', fill: true, tension: 0.4 }] },
        options: chartOpts
      });
    }
    if (data.revenue_trend?.length) {
      new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: { labels: data.revenue_trend.map(r => r.date), datasets: [{ label: '₹ Revenue', data: data.revenue_trend.map(r => r.revenue), borderColor: '#e94560', backgroundColor: 'rgba(233,69,96,0.1)', fill: true, tension: 0.1 }] },
        options: chartOpts
      });
    }
    if (data.delivery_perf?.length) {
      new Chart(document.getElementById('deliveryChart'), {
        type: 'bar',
        data: { labels: data.delivery_perf.map(r => r.name), datasets: [{ label: 'Avg Mins', data: data.delivery_perf.map(r => r.avg_time), backgroundColor: '#f59e0b' }] },
        options: chartOpts
      });
    }
  }).catch(e => console.error('Insights error', e));

  // Load alerts
  fetch('/api/alerts.php?action=list').then(r => r.json()).then(data => {
    const container = document.getElementById('alerts-container');
    document.getElementById('stat-alerts').textContent = data.count || '0';
    if (data.alerts?.length) {
      container.innerHTML = data.alerts.map(a =>
        `<div class="alert-item">🔔 <strong>${a.alert_type}</strong>: ${a.message} <small style="opacity:0.5;float:right">${a.created_at}</small></div>`
      ).join('');
    } else {
      container.innerHTML = '<p style="color:#22c55e;">✅ No active alerts. System is healthy.</p>';
    }
  }).catch(() => {
    document.getElementById('alerts-container').innerHTML = '<p style="color:var(--text-muted);">Could not load alerts.</p>';
  });
  </script>
</body>
</html>
