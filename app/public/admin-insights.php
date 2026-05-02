<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Insights — Feastly</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="/assets/css/main.css">
  <style>
    body { font-family: sans-serif; background: #0f0f0f; color: #eee; padding: 2rem; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
    .card { background: #1a1a2e; border-radius: 12px; padding: 1.5rem; }
    .insight-box { background: #16213e; border-left: 4px solid #e94560; padding: 1rem; margin: 0.5rem 0; border-radius: 6px; }
    canvas { max-height: 300px; }
    @media(max-width: 768px) { .grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
      <h1>📊 AI Insights Dashboard</h1>
      <a href="/index.php" class="btn btn-secondary">Back to Store</a>
  </div>
  
  <div id="insights-container" style="margin-bottom: 2rem;"></div>
  
  <div class="grid">
    <div class="card"><h3>Top Dishes (Last 30 Days)</h3><canvas id="topItemsChart"></canvas></div>
    <div class="card"><h3>Peak Ordering Hours</h3><canvas id="peakHoursChart"></canvas></div>
    <div class="card"><h3>Revenue Trend (Last 90 Days)</h3><canvas id="revenueChart"></canvas></div>
    <div class="card"><h3>Delivery Performance</h3><canvas id="deliveryChart"></canvas></div>
  </div>

  <script>
  fetch('/api/insights.php').then(r => r.json()).then(data => {
    // Render AI insights
    const box = document.getElementById('insights-container');
    data.ai_insights.forEach(msg => {
      box.innerHTML += `<div class="insight-box">${msg}</div>`;
    });

    // Top Items Bar Chart
    new Chart(document.getElementById('topItemsChart'), {
      type: 'bar',
      data: {
        labels: data.top_items.map(i => i.dish_name),
        datasets: [{ label: 'Orders', data: data.top_items.map(i => i.order_count),
                     backgroundColor: '#e94560' }]
      }
    });

    // Peak Hours Line Chart
    new Chart(document.getElementById('peakHoursChart'), {
      type: 'line',
      data: {
        labels: data.peak_hours.map(h => `${h.hour}:00`),
        datasets: [{ label: 'Orders', data: data.peak_hours.map(h => h.order_count),
                     borderColor: '#0f3460', fill: true, tension: 0.4 }]
      }
    });

    // Revenue Trend Line Chart
    new Chart(document.getElementById('revenueChart'), {
      type: 'line',
      data: {
        labels: data.revenue_trend.map(r => r.date),
        datasets: [{ label: '₹ Revenue', data: data.revenue_trend.map(r => r.revenue),
                     borderColor: '#e94560', fill: false, tension: 0.1 }]
      }
    });

    // Delivery Bar Chart
    new Chart(document.getElementById('deliveryChart'), {
      type: 'bar',
      data: {
        labels: data.delivery_perf.map(r => r.name),
        datasets: [{ label: 'Avg Mins', data: data.delivery_perf.map(r => r.avg_time),
                     backgroundColor: '#16213e' }]
      }
    });
  });
  </script>
</body>
</html>
