<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/../../src/controllers/AuthController.php';
require_once __DIR__ . '/../../src/config/Database.php';

// Strict Admin check
AuthController::requireAdmin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'list') {
        $restaurants = Database::query('SELECT id, name, avg_rating, is_active FROM restaurants ORDER BY name ASC')->fetchAll();
        echo json_encode($restaurants);
        exit;
    }
    
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $restaurant = Database::query('SELECT * FROM restaurants WHERE id = ?', [$id])->fetch();
        $menu = Database::query('SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name', [$id])->fetchAll();
        echo json_encode(['restaurant' => $restaurant, 'menu' => $menu]);
        exit;
    }
    
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action === 'update_restaurant') {
        $id = (int)$input['id'];
        $isActive = (int)$input['is_active'];
        $isEco = (int)$input['is_eco_friendly'];
        $ecoScore = (int)$input['eco_score'];
        $avgDelivery = (int)$input['avg_delivery_time'];
        
        Database::query(
            'UPDATE restaurants SET is_active=?, is_eco_friendly=?, eco_score=?, avg_delivery_time=? WHERE id=?',
            [$isActive, $isEco, $ecoScore, $avgDelivery, $id]
        );
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'update_menu_status') {
        $id = (int)$input['id'];
        $isAvail = (int)$input['is_available'];
        Database::query('UPDATE menu_items SET is_available=? WHERE id=?', [$isAvail, $id]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'update_menu_price') {
        $id = (int)$input['id'];
        $price = (float)$input['price'];
        Database::query('UPDATE menu_items SET price=? WHERE id=?', [$price, $id]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['error' => 'Invalid POST action']);
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
