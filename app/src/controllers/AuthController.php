<?php
require_once __DIR__ . '/../config/Database.php';

class AuthController {

    public function register(string $name, string $email, string $password): array {
        // Validate
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
            return ['success' => false, 'message' => 'Invalid email address'];

        if (strlen($password) < 8)
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];

        // Check duplicate
        $existing = Database::query('SELECT id FROM users WHERE email = ?', [$email])->fetch();
        if ($existing)
            return ['success' => false, 'message' => 'Email already registered'];

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        Database::query(
            'INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())',
            [$name, $email, $hash]
        );

        return ['success' => true, 'message' => 'Account created! Please sign in.'];
    }

    public function login(string $email, string $password): array {
        $user = Database::query('SELECT * FROM users WHERE email = ?', [$email])->fetch();
        if (!$user || !password_verify($password, $user['password_hash']))
            return ['success' => false, 'message' => 'Invalid email or password'];

        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'] ?? 'customer';

        return ['success' => true, 'redirect' => '/index.php'];
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        header('Location: /login.php');
        exit;
    }

    public static function requireLogin(): void {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            die('Access denied');
        }
    }
}
