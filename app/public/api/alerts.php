<?php
require_once __DIR__ . '/../../src/config/Database.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';
$pdo = Database::getInstance()->getConnection();

if ($action === 'list') {
  $alerts = $pdo->query("
    SELECT * FROM admin_alerts WHERE is_read = 0
    ORDER BY created_at DESC LIMIT 20
  ")->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['alerts' => $alerts, 'count' => count($alerts)]);
}

if ($action === 'mark_read' && isset($_GET['id'])) {
  $pdo->prepare("UPDATE admin_alerts SET is_read = 1 WHERE id = ?")->execute([(int)$_GET['id']]);
  echo json_encode(['success' => true]);
}
