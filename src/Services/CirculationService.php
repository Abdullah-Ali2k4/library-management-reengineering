<?php

declare(strict_types=1);

final class CirculationService
{
    public static function issueBook(mysqli $conn, int $bookId, int $memberId, string $dueDate): void
    {
        $conn->begin_transaction();

        try {
            $lockStmt = $conn->prepare('SELECT available_copies FROM books WHERE book_id = ? FOR UPDATE');
            $lockStmt->bind_param('i', $bookId);
            $lockStmt->execute();
            $book = $lockStmt->get_result()->fetch_assoc();
            $lockStmt->close();

            if (!$book || (int)$book['available_copies'] < 1) {
                throw new RuntimeException('This book is no longer available.');
            }

            $issueStmt = $conn->prepare("INSERT INTO issued_books (book_id, member_id, issue_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'issued')");
            $issueStmt->bind_param('iis', $bookId, $memberId, $dueDate);
            $issueStmt->execute();
            $issueStmt->close();

            $updateStmt = $conn->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?');
            $updateStmt->bind_param('i', $bookId);
            $updateStmt->execute();
            $updateStmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public static function returnBook(mysqli $conn, int $issueId): void
    {
        $conn->begin_transaction();

        try {
            $recordStmt = $conn->prepare('SELECT book_id, status FROM issued_books WHERE issue_id = ? FOR UPDATE');
            $recordStmt->bind_param('i', $issueId);
            $recordStmt->execute();
            $record = $recordStmt->get_result()->fetch_assoc();
            $recordStmt->close();

            if (!$record) {
                throw new RuntimeException('Issue record not found.');
            }

            if (($record['status'] ?? '') === 'returned') {
                throw new RuntimeException('This book has already been returned.');
            }

            $returnStmt = $conn->prepare("UPDATE issued_books SET status = 'returned', return_date = CURDATE() WHERE issue_id = ?");
            $returnStmt->bind_param('i', $issueId);
            $returnStmt->execute();
            $returnStmt->close();

            $bookId = (int)$record['book_id'];
            $bookStmt = $conn->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?');
            $bookStmt->bind_param('i', $bookId);
            $bookStmt->execute();
            $bookStmt->close();

            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }
}
