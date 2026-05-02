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

        $stmtStatus = $db->prepare("INSERT INTO order_status (order_id, status) VALUES (?, 'placed')");
        $stmtStatus->execute([$orderId]);

        // Feature 1: Track history for AI
        $hour = (int)date('H');
        $timeSlot = 'night';
        if ($hour >= 6 && $hour < 12) $timeSlot = 'morning';
        elseif ($hour >= 12 && $hour < 17) $timeSlot = 'afternoon';
        elseif ($hour >= 17 && $hour < 21) $timeSlot = 'evening';

        $stmtHist = $db->prepare("INSERT INTO user_order_history (user_id, dish_id, time_of_day) VALUES (?, ?, ?)");
        foreach ($data['items'] as $item) {
            // we only track actual menu items, ignore combo items for now or insert if valid id
            if (is_numeric($item['id'])) {
                $stmtHist->execute([$userId, $item['id'], $timeSlot]);
            }
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
