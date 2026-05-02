<?php
require_once __DIR__ . '/../../src/config/Database.php';
require_once __DIR__ . '/../../src/helpers/time-predictor.php';
header('Content-Type: application/json');

$restaurantId = (int)($_GET['restaurant_id'] ?? 1); // default to main kitchen
$distanceKm   = (float)($_GET['distance_km'] ?? 2);

echo json_encode(predictDeliveryTime(Database::getConnection(), $restaurantId, $distanceKm));
