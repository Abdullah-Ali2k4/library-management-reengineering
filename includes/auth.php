<?php
require_once __DIR__ . '/../config/paths.php';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_start();
}
function isLoggedIn(): bool { return isset($_SESSION['user_id'], $_SESSION['role']); }
function requireLogin(): void {
    if (!isLoggedIn()) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            header('Location: ' . BASE_URL . 'auth/index.php');
            exit;
        }
        http_response_code(401); exit('Authentication required. Please sign in.');
    }
}
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array((string)($_SESSION['role'] ?? ''), $roles, true)) {
        http_response_code(403); exit('You are not authorized to perform this action.');
    }
}
function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    return ['id'=>(int)$_SESSION['user_id'], 'username'=>(string)($_SESSION['username'] ?? ''),
        'email'=>(string)($_SESSION['user_email'] ?? ''), 'role'=>(string)$_SESSION['role']];
}
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403); exit('Invalid or missing security token. Please return to the page and try again.');
    }
}
function requirePost(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST'); http_response_code(405); exit('Method not allowed.');
    }
}
