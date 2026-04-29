<?php
require_once __DIR__ . '/../config/Database.php';

class MenuItem {
    public static function getAll(): array {
        return Database::query("
            SELECT m.*, c.name as category_name 
            FROM menu_items m 
            JOIN categories c ON m.category_id = c.id 
            WHERE m.is_available = 1
        ")->fetchAll();
    }

    public static function getFeatured(int $limit = 6): array {
        return Database::query("
            SELECT m.*, c.name as category_name 
            FROM menu_items m 
            JOIN categories c ON m.category_id = c.id 
            WHERE m.is_available = 1 AND m.is_popular = 1
            LIMIT " . (int)$limit . "
        ")->fetchAll();
    }

    public static function getCategories(): array {
        return Database::query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
    }
}
