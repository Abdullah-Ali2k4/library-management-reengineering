<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

require_once '../config/db.php';
include '../includes/header.php';

$result = $conn->query("
    SELECT authors.*, COUNT(books.book_id) AS book_count
    FROM authors
    LEFT JOIN books ON books.author_id = authors.author_id
    GROUP BY authors.author_id
    ORDER BY authors.first_name
");
?>

<h1>All Authors</h1>

<p><a href="add.php" class="btn">+ Add New Author</a></p>

<div class="table-responsive">
<table>
    <tr>
        <th>Name</th>
        <th>Bio</th>
        <th># Books</th>
        <th>Actions</th>
    </tr>
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
            <td><?php echo htmlspecialchars(mb_strimwidth($row['bio'] ?? '', 0, 80, '...')) ?: '—'; ?></td>
            <td><?php echo (int)$row['book_count']; ?></td>
            <td class="actions">
                <a href="edit.php?id=<?php echo (int)$row['author_id']; ?>" class="btn-edit">Edit</a>
                <form method="POST" action="delete.php" class="inline-delete-form" onsubmit="return confirm('Delete this record?');">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$row['author_id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn-delete">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4">No authors found.</td></tr>
    <?php endif; ?>
</table>
</div>

<?php
include '../includes/footer.php';
$conn->close();
?>
