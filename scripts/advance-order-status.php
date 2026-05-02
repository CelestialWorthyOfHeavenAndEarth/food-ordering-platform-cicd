<?php
require_once __DIR__ . '/../app/src/config/Database.php';

// Status progression rules: minutes elapsed => next status
$transitions = [
  'placed'          => ['next' => 'confirmed',       'after_minutes' => 1],
  'confirmed'       => ['next' => 'preparing',       'after_minutes' => 3],
  'preparing'       => ['next' => 'cooking',         'after_minutes' => 8],
  'cooking'         => ['next' => 'out_for_delivery','after_minutes' => 15],
  'out_for_delivery'=> ['next' => 'delivered',       'after_minutes' => 25],
];

foreach ($transitions as $currentStatus => $rule) {
  Database::query("
    UPDATE order_status
    SET status = ?, updated_at = NOW()
    WHERE status = ?
      AND TIMESTAMPDIFF(MINUTE, updated_at, NOW()) >= ?
  ", [
    $rule['next'],
    $currentStatus,
    $rule['after_minutes']
  ]);
}
echo "[" . date('Y-m-d H:i:s') . "] Status advancement complete.\n";
