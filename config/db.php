<?php
// Configure DB_HOST, DB_USER, DB_PASS and DB_NAME in the server environment.
// XAMPP defaults are retained for local development only.
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_name = getenv('DB_NAME') ?: 'library_db';
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_errno) {
    error_log('Library DB connection failed (' . $conn->connect_errno . '): ' . $conn->connect_error);
    http_response_code(500);
    exit('The application is temporarily unavailable. Please try again later.');
}
if (!$conn->set_charset('utf8mb4')) {
    error_log('Library DB charset configuration failed: ' . $conn->error);
    http_response_code(500); exit('The application is temporarily unavailable. Please try again later.');
}
