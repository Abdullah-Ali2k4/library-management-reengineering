<?php
// Lightweight static regression checks; run: php tests/security_regression.php
$root = dirname(__DIR__);
$checks = [];
function checkResult(string $label, bool $ok): void { global $checks; $checks[] = [$label, $ok]; }
$auth = file_get_contents($root . '/includes/auth.php');
checkResult('Central requireLogin helper exists', strpos($auth, 'function requireLogin') !== false);
checkResult('Role checks fail closed with HTTP 403', strpos($auth, 'http_response_code(403)') !== false);
checkResult('CSRF uses hash_equals', strpos($auth, 'hash_equals') !== false);
foreach (['books','authors','members'] as $folder) {
    $handler = file_get_contents($root . '/' . $folder . '/delete.php');
    $view = file_get_contents($root . '/' . $folder . '/view.php');
    checkResult($folder . ' deletion requires POST', strpos($handler, 'requirePost()') !== false);
    checkResult($folder . ' deletion validates CSRF', strpos($handler, 'requireCsrf()') !== false);
    checkResult($folder . ' deletion requires admin', strpos($handler, "requireRole('admin')") !== false);
    checkResult($folder . ' view uses POST form, not GET delete link', strpos($view, 'method="POST" action="delete.php"') !== false && strpos($view, 'delete.php?id=') === false);
}
$paths=file_get_contents($root.'/config/paths.php');
checkResult('Path debug endpoint removed', strpos($paths, 'debug_paths') === false);
checkResult('Unsafe connection tester removed', !file_exists($root.'/test_connection.php'));
$register=file_get_contents($root.'/auth/register.php');
checkResult('Registration assigns staff server-side', strpos($register, 'staff') !== false);
checkResult('Registration ignores submitted role', strpos($register, "name=\"role\"") === false);
$login=file_get_contents($root.'/auth/login_handler.php');
checkResult('Login accepts both approved roles', strpos($login, "['admin','staff']") !== false);
foreach ($checks as [$label,$ok]) echo ($ok ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
$failed=count(array_filter($checks, fn($c)=>!$c[1]));
echo sprintf("\n%d checks, %d failed.\n", count($checks), $failed);
exit($failed ? 1 : 0);
