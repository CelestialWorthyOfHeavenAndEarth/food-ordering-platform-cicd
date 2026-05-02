<?php
require_once __DIR__ . '/../../src/config/Database.php';
require_once __DIR__ . '/../../src/helpers/search-parser.php';
header('Content-Type: application/json');

$raw    = trim($_GET['q'] ?? '');
if (!$raw) { echo json_encode([]); exit; }

$parsed  = parseSearchQuery($raw);
$where   = ['m.is_available = 1'];
$params  = [];

if ($parsed['max_price'] !== null) {
  $where[]  = 'm.price <= :max_price';
  $params['max_price'] = $parsed['max_price'];
}

foreach ($parsed['tags'] as $i => $tag) {
  $key     = "tag{$i}";
  $where[] = "m.tags LIKE :{$key}";
  $params[$key] = "%{$tag}%";
}

$relevanceSelect = '0 AS relevance';
if (!empty($parsed['keywords'])) {
  $where[]  = "MATCH(m.name, m.description, m.tags) AGAINST(:kw IN BOOLEAN MODE)";
  $params['kw'] = $parsed['keywords'] . '*';
  $relevanceSelect = "MATCH(m.name, m.description, m.tags) AGAINST(:kw2 IN BOOLEAN MODE) AS relevance";
  $params['kw2'] = $parsed['keywords'] . '*';
}

$sql = "SELECT m.id, m.name as dish_name, m.price, m.tags, m.image_url, {$relevanceSelect}
        FROM menu_items m
        WHERE " . implode(' AND ', $where) . "
        ORDER BY relevance DESC, m.price ASC
        LIMIT 20";

$stmt = Database::getConnection()->prepare($sql);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
