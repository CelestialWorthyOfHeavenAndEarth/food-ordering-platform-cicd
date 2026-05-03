<?php
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

require_once __DIR__ . '/../../src/config/Database.php';
$db     = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List all orders with user info and items
    $status_filter = $_GET['status'] ?? 'all';
    $sql = "
        SELECT o.id, o.status, o.total_amount, o.delivery_address, o.payment_method,
               o.notes, o.created_at, u.name AS customer_name, u.email AS customer_email
        FROM orders o
        JOIN users u ON u.id = o.user_id
    ";
    if ($status_filter !== 'all') {
        $sql .= " WHERE o.status = " . $db->quote($status_filter);
    }
    $sql .= " ORDER BY o.created_at DESC LIMIT 100";

    $orders = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for each order
    $itemStmt = $db->prepare("
        SELECT oi.quantity, oi.unit_price, m.name
        FROM order_items oi JOIN menu_items m ON m.id = oi.menu_item_id
        WHERE oi.order_id = ?
    ");
    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['orders' => $orders]);
    exit;
}

if ($method === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true);
    $order_id = (int)($body['order_id'] ?? 0);
    $new_status = $body['status'] ?? '';

    $allowed = ['confirmed','preparing','cooking','out_for_delivery','delivered','cancelled'];
    if (!$order_id || !in_array($new_status, $allowed)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order_id or status']);
        exit;
    }

    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
    $db->prepare("INSERT INTO order_status (order_id, status) VALUES (?, ?)")->execute([$order_id, $new_status]);

    echo json_encode(['success' => true, 'order_id' => $order_id, 'status' => $new_status]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
