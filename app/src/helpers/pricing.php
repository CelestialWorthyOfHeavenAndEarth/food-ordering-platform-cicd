<?php
require_once __DIR__ . '/../config/Database.php';

function getPricingConfig(): array {
  $rows = Database::query("SELECT config_key, config_value FROM pricing_config")->fetchAll(PDO::FETCH_ASSOC);
  return array_column($rows, 'config_value', 'config_key');
}

function calculateOrderBreakdown(float $subtotal, float $distanceKm): array {
  $cfg = getPricingConfig();

  $deliveryFee  = $cfg['base_delivery_fee'];
  if ($distanceKm > 3) {
    $deliveryFee += ($distanceKm - 3) * $cfg['per_km_rate'];
  }

  $gst          = $subtotal * ($cfg['gst_percent'] / 100);
  $platformFee  = $cfg['platform_fee'];
  $packingCharge= $cfg['packing_charge'];
  $total        = $subtotal + $deliveryFee + $gst + $platformFee + $packingCharge;

  return [
    'subtotal'      => round($subtotal, 2),
    'delivery_fee'  => round($deliveryFee, 2),
    'gst'           => round($gst, 2),
    'platform_fee'  => round($platformFee, 2),
    'packing_charge'=> round($packingCharge, 2),
    'total'         => round($total, 2),
  ];
}
