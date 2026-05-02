<?php
require_once __DIR__ . '/../../src/config/Database.php';
require_once __DIR__ . '/../../src/helpers/haversine.php';

header('Content-Type: application/json');

$userLat = (float)($_GET['lat'] ?? 0);
$userLon = (float)($_GET['lon'] ?? 0);

$restaurants = Database::query("
  SELECT id, name, avg_rating, avg_delivery_time, latitude, longitude, is_eco_friendly
  FROM restaurants WHERE is_active = 1
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($restaurants as &$r) {
  $dist = haversineDistance($userLat, $userLon, $r['latitude'], $r['longitude']);
  $r['distance_km'] = round($dist, 2);

  $ratingScore   = ($r['avg_rating'] / 5) * 0.40;
  $timeScore     = (1 / max($r['avg_delivery_time'], 1)) * 0.35;
  $distScore     = (1 / max($dist, 0.1)) * 0.25;

  $r['smart_score'] = round($ratingScore + $timeScore + $distScore, 4);
}

usort($restaurants, fn($a, $b) => $b['smart_score'] <=> $a['smart_score']);
echo json_encode($restaurants);
