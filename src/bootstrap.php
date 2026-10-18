<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('BASE_URL')) {
    $projectRoot = realpath(APP_ROOT);
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

    $base = '/';
    if ($projectRoot !== false && $docRoot !== false) {
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $docRoot = str_replace('\\', '/', $docRoot);

        if (stripos($projectRoot, $docRoot) === 0) {
            $base = substr($projectRoot, strlen($docRoot));
            $base = '/' . trim($base, '/');
            if ($base !== '/') {
                $base .= '/';
            }
        }
    }

    define('BASE_URL', $base);
}

if (!defined('APP_ENV')) {
    define('APP_ENV', getenv('APP_ENV') ?: 'development');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
