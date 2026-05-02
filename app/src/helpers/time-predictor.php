<?php
function predictDeliveryTime(PDO $pdo, int $restaurantId, float $distanceKm): array {
  // Get restaurant base prep time
  $r = $pdo->prepare("SELECT avg_delivery_time FROM restaurants WHERE id = ?");
  $r->execute([$restaurantId]);
  $basePrepTime = (int)($r->fetchColumn() ?: 20);

  // Get current hour order count
  $cur = $pdo->prepare("
    SELECT COUNT(*) FROM orders
    WHERE restaurant_id = ? AND created_at >= NOW() - INTERVAL 1 HOUR
  ");
  $cur->execute([$restaurantId]);
  $currentOrders = (int)$cur->fetchColumn();

  // Get 7-day average hourly orders
  $avg = $pdo->prepare("
    SELECT COUNT(*) / (7 * 24) FROM orders
    WHERE restaurant_id = ? AND created_at >= NOW() - INTERVAL 7 DAY
  ");
  $avg->execute([$restaurantId]);
  $avgHourlyOrders = max((float)$avg->fetchColumn(), 1);

  // Load factor: ratio of current vs average
  $loadFactor = $currentOrders / $avgHourlyOrders;

  // Delivery distance time: assume 25 km/h avg speed
  $distanceMinutes = ($distanceKm / 25) * 60;

  // Final prediction
  $predicted = $basePrepTime + ($loadFactor * 1.5) + $distanceMinutes;
  $minEst    = (int)max($predicted * 0.85, 10);
  $maxEst    = (int)($predicted * 1.15);

  return [
    'estimated_minutes' => round($predicted),
    'range'             => "{$minEst}–{$maxEst} mins",
    'load_factor'       => round($loadFactor, 2),
  ];
}
