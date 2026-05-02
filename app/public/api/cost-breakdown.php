<?php
require_once __DIR__ . '/../../src/config/Database.php';
require_once __DIR__ . '/../../src/helpers/pricing.php';
header('Content-Type: application/json');

$subtotal    = (float)($_GET['subtotal']     ?? 0);
$distanceKm  = (float)($_GET['distance_km'] ?? 2);

echo json_encode(calculateOrderBreakdown($subtotal, $distanceKm));
