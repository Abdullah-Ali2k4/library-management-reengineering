<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/AuthService.php';

function isLoggedIn(): bool
{
    return AuthService::isLoggedIn();
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            header('Location: ' . BASE_URL . 'auth/index.php');
            exit;
        }

        http_response_code(401);
        exit('Authentication required. Please sign in.');
    }
}

function requireRole(string ...$roles): void
{
    requireLogin();

    $role = (string)($_SESSION['role'] ?? '');
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        exit('You are not authorized to perform this action.');
    }
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
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid or missing security token. Please return to the page and try again.');
    }
}

function requirePost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        exit('Method not allowed.');
    }
}
