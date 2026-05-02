<?php
require_once __DIR__ . '/../../src/config/Database.php';
header('Content-Type: application/json');

session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

// Guard: admin only
// if ($_SESSION['role'] !== 'admin') { http_response_code(403); exit; }

$data = [];

$pdo = Database::getInstance()->getConnection();

// Most ordered items last 30 days
$data['top_items'] = $pdo->query("
  SELECT m.name as dish_name, COUNT(oi.id) AS order_count
  FROM order_items oi
  JOIN menu_items m ON m.id = oi.menu_item_id
  WHERE oi.order_id IN (SELECT id FROM orders WHERE created_at >= NOW() - INTERVAL 30 DAY)
  GROUP BY m.id ORDER BY order_count DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Orders by hour of day
$data['peak_hours'] = $pdo->query("
  SELECT HOUR(created_at) AS hour, COUNT(*) AS order_count
  FROM orders WHERE created_at >= NOW() - INTERVAL 30 DAY
  GROUP BY HOUR(created_at) ORDER BY hour
")->fetchAll(PDO::FETCH_ASSOC);

// Daily revenue last 90 days
$data['revenue_trend'] = $pdo->query("
  SELECT DATE(created_at) AS date, SUM(total_amount) AS revenue
  FROM orders WHERE created_at >= NOW() - INTERVAL 90 DAY
  GROUP BY DATE(created_at) ORDER BY date
")->fetchAll(PDO::FETCH_ASSOC);

// Avg delivery time per restaurant
$data['delivery_perf'] = $pdo->query("
  SELECT r.name, AVG(r.avg_delivery_time) AS avg_time
  FROM restaurants r GROUP BY r.id ORDER BY avg_time ASC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Auto-generate plain English insights
$insights = [];
if (!empty($data['top_items'])) {
  $top = $data['top_items'][0];
  $insights[] = "🍽️ '{$top['dish_name']}' is your top seller with {$top['order_count']} orders in the last 30 days.";
}
if (!empty($data['peak_hours'])) {
  $peak = array_reduce($data['peak_hours'], fn($carry, $h) =>
    (!$carry || $h['order_count'] > $carry['order_count']) ? $h : $carry);
  $insights[] = "⏰ Peak ordering hour is {$peak['hour']}:00 with {$peak['order_count']} orders.";
}
if (!empty($data['revenue_trend'])) {
  $total = array_sum(array_column($data['revenue_trend'], 'revenue'));
  $insights[] = "💰 Total revenue in the last 90 days: ₹" . number_format($total, 2) . ".";
}

$data['ai_insights'] = $insights;

// User count
$data['user_count'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

echo json_encode($data);
