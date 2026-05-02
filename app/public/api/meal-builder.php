<?php
require_once __DIR__ . '/../../src/config/Database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // Return all available items grouped by category
  $items = Database::query("
    SELECT m.id, m.name as dish_name, m.price, c.name as category, m.image_url, m.tags
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id
    WHERE m.is_available = 1 
    ORDER BY c.sort_order, m.name
  ")->fetchAll(PDO::FETCH_ASSOC);

  $grouped = [];
  foreach ($items as $item) {
    $grouped[$item['category']][] = $item;
  }
  echo json_encode($grouped);
  exit;
}

if ($method === 'POST') {
  $body     = json_decode(file_get_contents('php://input'), true);
  $userId   = (int)($body['user_id']  ?? 0);
  $itemIds  = $body['item_ids']        ?? [];

  if (empty($itemIds)) { echo json_encode(['error' => 'No items selected']); exit; }
  if (!$userId) { echo json_encode(['error' => 'User not logged in']); exit; }

  // Fetch prices
  $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
  $stmt = Database::getConnection()->prepare("SELECT id, price FROM menu_items WHERE id IN ($placeholders)");
  $stmt->execute($itemIds);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $basePrice = array_sum(array_column($items, 'price'));
  $count     = count($items);
  $discount  = match(true) {
    $count >= 4 => 15.0,
    $count === 3 => 10.0,
    $count === 2 => 5.0,
    default     => 0.0,
  };
  $finalPrice = $basePrice * (1 - $discount / 100);

  // Save combo
  $stmt = Database::getConnection()->prepare("INSERT INTO meal_combos (user_id, items, base_price, discount_percentage, final_price) VALUES (?, ?, ?, ?, ?)");
  $stmt->execute([$userId, json_encode($itemIds), $basePrice, $discount, $finalPrice]);
  $comboId = Database::getConnection()->lastInsertId();

  echo json_encode([
    'combo_id'    => $comboId,
    'base_price'  => $basePrice,
    'discount'    => $discount,
    'final_price' => round($finalPrice, 2),
    'saved'       => true
  ]);
}
