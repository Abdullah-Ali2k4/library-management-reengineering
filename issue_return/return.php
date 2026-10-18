<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

// Only show records that are still currently issued (not yet returned)
$issued_result = $conn->query("
    SELECT issued_books.issue_id, books.title, members.full_name, issued_books.due_date
    FROM issued_books
    JOIN books ON books.book_id = issued_books.book_id
    JOIN members ON members.member_id = issued_books.member_id
    WHERE issued_books.status = 'issued'
    ORDER BY issued_books.due_date ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $issue_id = trim($_POST['issue_id'] ?? '');

    if ($issue_id === '') {
        $errors[] = "Please select a book to return.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // Lock and confirm this issue record is still active
            $check = $conn->prepare("SELECT book_id, status FROM issued_books WHERE issue_id = ? FOR UPDATE");
            $check->bind_param("i", $issue_id);
            $check->execute();
            $record = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$record) {
                throw new Exception("Issue record not found.");
            }
            if ($record['status'] === 'returned') {
                throw new Exception("This book has already been returned.");
            }

            $return_date = date('Y-m-d');

            $update_issue = $conn->prepare("UPDATE issued_books SET status = 'returned', return_date = ? WHERE issue_id = ?");
            $update_issue->bind_param("si", $return_date, $issue_id);
            $update_issue->execute();
            $update_issue->close();

            $update_book = $conn->prepare("UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?");
            $update_book->bind_param("i", $record['book_id']);
            $update_book->execute();
            $update_book->close();

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }

        // Refresh the list after a successful return
        $issued_result = $conn->query("
            SELECT issued_books.issue_id, books.title, members.full_name, issued_books.due_date
            FROM issued_books
            JOIN books ON books.book_id = issued_books.book_id
            JOIN members ON members.member_id = issued_books.member_id
            WHERE issued_books.status = 'issued'
            ORDER BY issued_books.due_date ASC
        ");
    }
}

include '../includes/header.php';
?>

<h1>Return a Book</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Book marked as returned successfully!</p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($issued_result->num_rows > 0): ?>
<form method="POST" action="return.php" class="validate-form">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <label for="issue_id">Currently Issued Books</label>
    <select id="issue_id" name="issue_id" required>
        <option value="">-- Select a book to return --</option>
        <?php while ($row = $issued_result->fetch_assoc()): ?>
            <option value="<?php echo $row['issue_id']; ?>">
                <?php echo htmlspecialchars($row['title']); ?> — borrowed by <?php echo htmlspecialchars($row['full_name']); ?> (due <?php echo htmlspecialchars($row['due_date']); ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">Mark as Returned</button>
</form>
<?php else: ?>
    <p>No books are currently issued.</p>
<?php endif; ?>

<?php
include '../includes/footer.php';
$conn->close();
?>