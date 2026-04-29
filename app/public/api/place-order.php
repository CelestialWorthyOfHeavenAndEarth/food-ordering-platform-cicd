<?php
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../src/helpers/Security.php';
Security::verify_csrf();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please sign in to place an order.', 'redirect' => '/login.php']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['items']) || empty($data['address'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order data. Please check your cart and address.']);
    exit;
}

require_once __DIR__ . '/../../src/controllers/OrderController.php';

$orderController = new OrderController();
$result = $orderController->placeOrder($_SESSION['user_id'], $data);

if ($result['success']) {
    echo json_encode($result);
} else {
    http_response_code(500);
    echo json_encode($result);
}
