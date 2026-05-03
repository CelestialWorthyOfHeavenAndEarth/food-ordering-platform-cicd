<?php
session_start([
    'cookie_secure'   => false,
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

// CSRF check safe for JSON body (read from header)
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrfSession = $_SESSION['csrf_token'] ?? '';
if (!$csrfSession || !hash_equals($csrfSession, $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['error' => 'Security check failed. Please refresh and try again.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please sign in to place an order.', 'redirect' => '/login.php']);
    exit;
}

// Block admins from placing orders
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admins cannot place orders.']);
    exit;
}

$input = file_get_contents('php://input');
$data  = json_decode($input, true);

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
