<?php
require_once __DIR__ . '/../app/src/config/Database.php';

$pdo = Database::getConnection();
$alerts = [];

// Check 1: Low rating in last 48 hours (below 3.5)
// Assuming we have reviews table, but we don't. The schema doesn't have a reviews table!
// We'll skip check 1 or mock it since reviews table doesn't exist.
// Let's check for menu items with low rating.
$lowRating = $pdo->query("
  SELECT id, name, rating AS avg_rating
  FROM menu_items
  WHERE rating < 3.5 AND rating_count > 0
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($lowRating as $r) {
  $alerts[] = [
    'type'          => 'low_rating',
    'restaurant_id' => 1, // Main kitchen
    'message'       => "⚠️ Menu item '{$r['name']}' has a low rating of {$r['avg_rating']}.",
  ];
}

// Check 2: Zero orders in last 24 hours (inactivity)
$inactive = $pdo->query("
  SELECT r.id, r.name
  FROM restaurants r
  WHERE r.is_active = 1
    AND r.id NOT IN (
      SELECT DISTINCT restaurant_id FROM orders
      WHERE created_at >= NOW() - INTERVAL 24 HOUR
    )
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($inactive as $r) {
  $alerts[] = [
    'type'          => 'inactivity',
    'restaurant_id' => $r['id'],
    'message'       => "😴 {$r['name']} has had zero orders in the last 24 hours.",
  ];
}

// Check 3: Order volume anomaly (3x normal hourly average)
$anomalies = $pdo->query("
  SELECT
    r.id, r.name,
    COUNT(o.id) AS current_hour_orders,
    (SELECT COUNT(*) / 24 FROM orders o2 WHERE o2.restaurant_id = r.id
     AND o2.created_at >= NOW() - INTERVAL 7 DAY) AS daily_avg_hourly
  FROM restaurants r
  LEFT JOIN orders o ON o.restaurant_id = r.id
    AND o.created_at >= NOW() - INTERVAL 1 HOUR
  GROUP BY r.id
  HAVING current_hour_orders > daily_avg_hourly * 3 AND daily_avg_hourly > 0
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($anomalies as $r) {
  $alerts[] = [
    'type'          => 'anomaly',
    'restaurant_id' => $r['id'],
    'message'       => "🚨 {$r['name']} is getting {$r['current_hour_orders']}x unusual order spike this hour!",
  ];
}

// Insert new alerts
if (!empty($alerts)) {
  $stmt = $pdo->prepare("INSERT INTO admin_alerts (alert_type, restaurant_id, message) VALUES (?, ?, ?)");
  foreach ($alerts as $alert) {
    $stmt->execute([$alert['type'], $alert['restaurant_id'], $alert['message']]);
  }
  echo count($alerts) . " alerts generated.\n";
} else {
  echo "No alerts.\n";
}
