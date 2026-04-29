<?php
require_once __DIR__ . '/../models/Order.php';

class OrderController {
    public function placeOrder(int $userId, array $data): array {
        if (empty($data['items']) || empty($data['address'])) {
            return ['success' => false, 'error' => 'Invalid order data. Please check your cart and address.'];
        }

        try {
            $orderId = Order::create($userId, $data);
            return ['success' => true, 'order_id' => $orderId, 'message' => 'Order placed successfully!'];
        } catch (Exception $e) {
            error_log('Order failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to place order. Please try again later.'];
        }
    }

    public function getUserHistory(int $userId): array {
        $orders = Order::getByUser($userId);
        foreach ($orders as &$order) {
            $order['items'] = Order::getItems($order['id']);
        }
        return $orders;
    }
}
