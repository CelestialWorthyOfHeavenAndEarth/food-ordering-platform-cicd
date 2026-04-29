<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    public static function getById(int $id): ?array {
        $user = Database::query("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?", [$id])->fetch();
        return $user ?: null;
    }
}
