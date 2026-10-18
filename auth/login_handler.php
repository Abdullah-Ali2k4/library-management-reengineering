<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
requirePost();

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . BASE_URL . 'auth/index.php');
    exit;
}

$stmt = $conn->prepare('SELECT user_id, username, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
if (!$stmt) {
    error_log('Login query prepare failed: ' . $conn->error);
    $_SESSION['login_error'] = 'Unable to sign in right now.';
    header('Location: ' . BASE_URL . 'auth/index.php');
    exit;
}

$stmt->bind_param('s', $email);
if (!$stmt->execute()) {
    error_log('Login query failed: ' . $stmt->error);
    $_SESSION['login_error'] = 'Unable to sign in right now.';
    header('Location: ' . BASE_URL . 'auth/index.php');
    exit;
}

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user || !in_array($user['role'], ['admin', 'staff'], true) || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ' . BASE_URL . 'auth/index.php');
    exit;
}

AuthService::loginUser($user);
header('Location: ' . BASE_URL . 'index.php');
exit;
