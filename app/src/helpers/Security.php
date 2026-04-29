<?php

class Security {
    /**
     * Generate and return a CSRF token as a hidden input field.
     */
    public static function csrf_field(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }

    /**
     * Generate and return just the raw CSRF token string.
     */
    public static function csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify the CSRF token on POST requests.
     */
    public static function verify_csrf(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Check POST data first, then headers (for AJAX)
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'CSRF token mismatch or expired. Please refresh the page and try again.']));
            }
        }
    }
}
