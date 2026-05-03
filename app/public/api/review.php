<?php
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict']);
header('Content-Type: application/json');

require_once __DIR__ . '/../../src/config/Database.php';

$db     = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$rid    = (int)($_GET['restaurant_id'] ?? 0);

if ($method === 'GET') {
    if (!$rid) { echo json_encode(['error' => 'Missing restaurant_id']); exit; }
    $reviews = $db->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name AS user_name
        FROM restaurant_reviews r
        JOIN users u ON u.id = r.user_id
        WHERE r.restaurant_id = ?
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $reviews->execute([$rid]);
    $data = $reviews->fetchAll(PDO::FETCH_ASSOC);

    $avg = $db->prepare("SELECT ROUND(AVG(rating),1) as avg_rating, COUNT(*) as count FROM restaurant_reviews WHERE restaurant_id = ?");
    $avg->execute([$rid]);
    $stats = $avg->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['reviews' => $data, 'avg_rating' => $stats['avg_rating'], 'count' => (int)$stats['count']]);
    exit;
}

if ($method === 'POST') {
    if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Login required']); exit; }
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        http_response_code(403); echo json_encode(['error' => 'Admins cannot leave reviews']); exit;
    }

    $body   = json_decode(file_get_contents('php://input'), true);
    $rid    = (int)($body['restaurant_id'] ?? 0);
    $rating = (int)($body['rating'] ?? 0);
    $comment = trim($body['comment'] ?? '');

    if (!$rid || $rating < 1 || $rating > 5) {
        http_response_code(400); echo json_encode(['error' => 'Invalid rating']); exit;
    }

    $stmt = $db->prepare("INSERT INTO restaurant_reviews (user_id, restaurant_id, rating, comment) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment)");
    $stmt->execute([$_SESSION['user_id'], $rid, $rating, $comment]);

    // Recalculate avg rating on restaurants table
    $db->prepare("UPDATE restaurants SET avg_rating = (SELECT ROUND(AVG(rating),2) FROM restaurant_reviews WHERE restaurant_id = ?) WHERE id = ?")
       ->execute([$rid, $rid]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
