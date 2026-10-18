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
$exists = $conn->prepare('SELECT book_id FROM books WHERE book_id = ?');
$exists->bind_param('i', $id); $exists->execute();
$found = $exists->get_result()->fetch_assoc(); $exists->close();
if (!$found) { http_response_code(404); exit('Record not found.'); }
$check = $conn->prepare("SELECT COUNT(*) AS n FROM issued_books WHERE book_id = ? AND status = 'issued'");
$check->bind_param('i', $id); $check->execute();
$count = (int)$check->get_result()->fetch_assoc()['n']; $check->close();
if ($count > 0) { http_response_code(409); exit('This record has active loans and cannot be deleted until all items are returned.'); }
$history = $conn->prepare('SELECT COUNT(*) AS n FROM issued_books WHERE book_id = ?');
$history->bind_param('i', $id); $history->execute();
$historyCount = (int)$history->get_result()->fetch_assoc()['n']; $history->close();
if ($historyCount > 0) { http_response_code(409); exit('This record has lending history and must be retained to preserve the audit trail.'); }
$stmt = $conn->prepare('DELETE FROM books WHERE book_id = ?');
$stmt->bind_param('i', $id);
if (!$stmt->execute()) { error_log('Delete book failed: ' . $stmt->error); http_response_code(500); exit('Unable to delete this record.'); }
$stmt->close(); $conn->close();
header('Location: view.php?deleted=1'); exit;
