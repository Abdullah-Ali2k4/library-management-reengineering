<?php
if (!defined('BASE_URL')) {
    $projectRoot = realpath(__DIR__ . '/..');
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($projectRoot === false || $docRoot === false) { $base = '/'; }
    else {
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $docRoot = str_replace('\\', '/', $docRoot);
        $base = stripos($projectRoot, $docRoot) === 0 ? substr($projectRoot, strlen($docRoot)) : '';
        $base = '/' . trim($base, '/');
        if ($base !== '/') $base .= '/';
    }
    define('BASE_URL', $base);
}
