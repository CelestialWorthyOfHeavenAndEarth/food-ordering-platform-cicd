<?php
require_once __DIR__ . '/../../src/config/Database.php';
require_once __DIR__ . '/../../src/helpers/recommender.php';

header('Content-Type: application/json');
$userId  = (int)($_GET['user_id'] ?? 0);
if (!$userId) { echo json_encode(['error' => 'user_id required']); exit; }

$recommender = new DishRecommender(Database::getConnection());
echo json_encode($recommender->getRecommendations($userId));
