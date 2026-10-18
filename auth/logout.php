<?php
require_once __DIR__ . '/../includes/auth.php';
requirePost(); requireLogin(); requireCsrf();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', ['expires'=>time()-42000, 'path'=>$params['path'], 'domain'=>$params['domain'],
        'secure'=>$params['secure'], 'httponly'=>$params['httponly'], 'samesite'=>'Lax']);
}
session_destroy();
header('Location: ' . BASE_URL . 'auth/index.php'); exit;
