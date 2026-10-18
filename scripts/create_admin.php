<?php
// Run only from CLI: php scripts/create_admin.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/db.php';
$username = trim((string)readline('Administrator username: '));
$email = trim((string)readline('Administrator email: '));
$password = (string)readline('Administrator password (10+ characters): ');
if ($username === '' || strlen($username)>50 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<10) {
    fwrite(STDERR, "Invalid input. Username <= 50 chars, valid email, password >= 10 chars required.\n"); exit(1);
}
$check=$conn->prepare('SELECT user_id FROM users LIMIT 1');
$check->execute(); $check->store_result();
if ($check->num_rows > 0) { fwrite(STDERR, "Users already exist; refusing to create or elevate an account.\n"); exit(1); }
$check->close();
$hash=password_hash($password,PASSWORD_DEFAULT); $role='admin';
$stmt=$conn->prepare('INSERT INTO users (username,email,password_hash,role) VALUES (?,?,?,?)');
$stmt->bind_param('ssss',$username,$email,$hash,$role);
if (!$stmt->execute()) { error_log('Initial admin creation failed: '.$stmt->error); fwrite(STDERR,"Unable to create administrator. Check server logs.\n"); exit(1); }
echo "Initial administrator created. Store credentials securely.\n";
