<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

final class AuthService
{
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id'], $_SESSION['role']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                header('Location: ' . BASE_URL . 'auth/index.php');
                exit;
            }

            http_response_code(401);
            exit('Authentication required. Please sign in.');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();

        $role = (string)($_SESSION['role'] ?? '');
        if (!in_array($role, $roles, true)) {
            http_response_code(403);
            exit('You are not authorized to perform this action.');
        }
    }

    public static function currentUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => (int)$_SESSION['user_id'],
            'username' => (string)($_SESSION['username'] ?? ''),
            'email' => (string)($_SESSION['user_email'] ?? ''),
            'role' => (string)$_SESSION['role'],
        ];
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function requireCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            exit('Invalid or missing security token. Please return to the page and try again.');
        }
    }

    public static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            exit('Method not allowed.');
        }
    }

    public static function loginUser(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)($user['user_id'] ?? 0);
        $_SESSION['username'] = (string)($user['username'] ?? '');
        $_SESSION['user_email'] = (string)($user['email'] ?? '');
        $_SESSION['role'] = (string)($user['role'] ?? '');
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function logoutUser(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

function isLoggedIn(): bool
{
    return AuthService::isLoggedIn();
}

function requireLogin(): void
{
    AuthService::requireLogin();
}

function requireRole(string ...$roles): void
{
    AuthService::requireRole(...$roles);
}

function currentUser(): ?array
{
    return AuthService::currentUser();
}

function csrfToken(): string
{
    return AuthService::csrfToken();
}

function requireCsrf(): void
{
    AuthService::requireCsrf();
}

function requirePost(): void
{
    AuthService::requirePost();
}
