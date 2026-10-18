<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
requirePost();
requireCsrf();
require_once __DIR__ . '/../config/db.php';
$rawId = $_POST['id'] ?? '';
if (!is_string($rawId) || !preg_match('/^[1-9][0-9]*$/', $rawId) || (int)$rawId > 2147483647) {
    http_response_code(400); exit('Invalid record ID.');
}
$id = (int)$rawId;
$exists = $conn->prepare('SELECT author_id FROM authors WHERE author_id = ?');
$exists->bind_param('i', $id); $exists->execute();
$found = $exists->get_result()->fetch_assoc(); $exists->close();
if (!$found) { http_response_code(404); exit('Record not found.'); }
$stmt = $conn->prepare('DELETE FROM authors WHERE author_id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) { error_log('Delete author failed: ' . $stmt->error); http_response_code(500); exit('Unable to delete this record.'); }
$stmt->close(); $conn->close();
header('Location: view.php?deleted=1'); exit;
