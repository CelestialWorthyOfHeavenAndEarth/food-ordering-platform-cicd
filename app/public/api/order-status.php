<?php
require_once __DIR__ . '/../../src/config/Database.php';
header('Content-Type: application/json');

$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) { echo json_encode(['error' => 'order_id required']); exit; }

$stmt = Database::query("SELECT status, updated_at FROM order_status WHERE order_id = ? LIMIT 1", [$orderId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($row ?: ['status' => 'not_found']);
