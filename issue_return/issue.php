<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

// Only show books that currently have at least 1 available copy
$books_result = $conn->query("SELECT book_id, title, available_copies FROM books WHERE available_copies > 0 ORDER BY title");
$members_result = $conn->query("SELECT member_id, full_name FROM members ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $book_id   = trim($_POST['book_id'] ?? '');
    $member_id = trim($_POST['member_id'] ?? '');
    $due_date  = trim($_POST['due_date'] ?? '');

    if ($book_id === '') {
        $errors[] = "Please select a book.";
    }
    if ($member_id === '') {
        $errors[] = "Please select a member.";
    }
    if ($due_date === '') {
        $errors[] = "Please choose a due date.";
    } elseif (strtotime($due_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Due date cannot be in the past.";
    }

    if (empty($errors)) {
        // Use a transaction so the issue record and the copy count
        // either both succeed or both fail together
        $conn->begin_transaction();

        try {
            // Re-check availability inside the transaction to avoid a race
            // condition where two people issue the last copy at once
            $check = $conn->prepare("SELECT available_copies FROM books WHERE book_id = ? FOR UPDATE");
            $check->bind_param("i", $book_id);
            $check->execute();
            $book = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$book || $book['available_copies'] < 1) {
                throw new Exception("This book is no longer available.");
            }

            $issue_date = date('Y-m-d');

            $stmt = $conn->prepare("INSERT INTO issued_books (book_id, member_id, issue_date, due_date, status) VALUES (?, ?, ?, ?, 'issued')");
            $stmt->bind_param("iiss", $book_id, $member_id, $issue_date, $due_date);
            $stmt->execute();
            $stmt->close();

            $update = $conn->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
            $update->bind_param("i", $book_id);
            $update->execute();
            $update->close();

            $conn->commit();
            $success = true;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<h1>Issue a Book</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Book issued successfully!
        <a href="status.php">View issued books</a>
    </p>
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

<form method="POST" action="issue.php" class="validate-form">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <label for="book_id">Book</label>
    <select id="book_id" name="book_id" required>
        <option value="">-- Select Book --</option>
        <?php while ($book = $books_result->fetch_assoc()): ?>
            <option value="<?php echo $book['book_id']; ?>">
                <?php echo htmlspecialchars($book['title']); ?> (<?php echo (int)$book['available_copies']; ?> available)
            </option>
        <?php endwhile; ?>
    </select>

    <label for="member_id">Member</label>
    <select id="member_id" name="member_id" required>
        <option value="">-- Select Member --</option>
        <?php while ($member = $members_result->fetch_assoc()): ?>
            <option value="<?php echo $member['member_id']; ?>">
                <?php echo htmlspecialchars($member['full_name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="due_date">Due Date</label>
    <input type="date" id="due_date" name="due_date" required
           value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>"
           min="<?php echo date('Y-m-d'); ?>">

    <button type="submit">Issue Book</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>