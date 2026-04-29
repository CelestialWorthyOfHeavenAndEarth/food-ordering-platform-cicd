<?php
require_once __DIR__ . '/../config/Database.php';

class Order {
    public static function create(int $userId, array $data): int {
        $db = Database::getConnection();
        
        $address = trim($data['address']);
        $notes = trim($data['notes'] ?? '');
        $paymentMethod = $data['payment_method'] === 'online' ? 'online' : 'cod';
        $deliveryFee = 40.00;
        
        $totalAmount = 0;
        foreach ($data['items'] as $item) {
            $totalAmount += ($item['price'] * $item['quantity']);
        }
        $totalAmount += $deliveryFee;

        $stmt = $db->prepare("INSERT INTO orders (user_id, status, total_amount, delivery_fee, delivery_address, payment_method, notes) VALUES (?, 'pending', ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $totalAmount, $deliveryFee, $address, $paymentMethod, $notes]);
        $orderId = $db->lastInsertId();

        $stmtItem = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
        foreach ($data['items'] as $item) {
            $stmtItem->execute([$orderId, $item['id'], $item['quantity'], $item['price']]);
        }

        return $orderId;
    }

    public static function getByUser(int $userId): array {
        return Database::query("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC", [$userId])->fetchAll();
    }
    
    public static function getItems(int $orderId): array {
        return Database::query("
            SELECT oi.*, m.name 
            FROM order_items oi 
            JOIN menu_items m ON oi.menu_item_id = m.id 
            WHERE oi.order_id = ?
        ", [$orderId])->fetchAll();
    }
}
