<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';
include '../includes/header.php';

$today = date('Y-m-d');

$result = $conn->query("
    SELECT issued_books.*, books.title, members.full_name
    FROM issued_books
    JOIN books ON books.book_id = issued_books.book_id
    JOIN members ON members.member_id = issued_books.member_id
    ORDER BY
        (issued_books.status = 'issued') DESC,
        issued_books.due_date ASC
");
?>

<h1>Issued &amp; Overdue Books</h1>

<div class="table-responsive">
<table>
    <tr>
        <th>Book</th>
        <th>Member</th>
        <th>Issue Date</th>
        <th>Due Date</th>
        <th>Return Date</th>
        <th>Status</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <?php
                $is_overdue = ($row['status'] === 'issued' && $row['due_date'] < $today);
            ?>
            <tr<?php echo $is_overdue ? ' style="background-color:#fdecea;"' : ''; ?>>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['issue_date']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td><?php echo $row['return_date'] ? htmlspecialchars($row['return_date']) : '—'; ?></td>
                <td>
                    <?php if ($row['status'] === 'returned'): ?>
                        <span style="color:#1f7a3d; font-weight:600;">Returned</span>
                    <?php elseif ($is_overdue): ?>
                        <span style="color:#c0392b; font-weight:600;">Overdue</span>
                    <?php else: ?>
                        <span style="color:#b8860b; font-weight:600;">Issued</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No issue records found.</td></tr>
    <?php endif; ?>
</table>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>
