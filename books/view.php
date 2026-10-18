<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
requireLogin();

include '../includes/header.php';

$result = $conn->query("
    SELECT books.*, authors.first_name, authors.last_name
    FROM books
    LEFT JOIN authors ON books.author_id = authors.author_id
    ORDER BY books.title
");
?>

<h1>All Books</h1>

<p><a href="add.php" class="btn">+ Add New Book</a></p>

<div class="table-responsive">
<table>
    <tr>
        <th>Title</th>
        <th>Author</th>
        <th>ISBN</th>
        <th>Category</th>
        <th>Available / Total</th>
        <th>Actions</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: '—'); ?></td>
            <td><?php echo htmlspecialchars($row['isbn'] ?: '—'); ?></td>
            <td><?php echo htmlspecialchars($row['category'] ?: '—'); ?></td>
            <td><?php echo (int)$row['available_copies']; ?> / <?php echo (int)$row['total_copies']; ?></td>
            <td class="actions">
                <a href="edit.php?id=<?php echo (int)$row['book_id']; ?>" class="btn-edit">Edit</a>
                <form method="POST" action="delete.php" class="inline-delete-form" onsubmit="return confirm('Delete this record?');">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$row['book_id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn-delete">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No books found.</td></tr>
    <?php endif; ?>
</table>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>
