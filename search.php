<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
require_once 'config/db.php';
include 'includes/header.php';

$query = trim($_GET['q'] ?? '');
$results = null;

if ($query !== '') {
    $like = "%" . $query . "%";
    $stmt = $conn->prepare("
        SELECT books.*, authors.first_name, authors.last_name
        FROM books
        LEFT JOIN authors ON books.author_id = authors.author_id
        WHERE books.title LIKE ?
           OR books.category LIKE ?
           OR authors.first_name LIKE ?
           OR authors.last_name LIKE ?
        ORDER BY books.title
    ");
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<h1>Search Books</h1>

<form method="GET" action="search.php" class="validate-form" style="display:flex; gap:10px; align-items:flex-start; max-width:600px;">
    <div style="flex:1;">
        <label for="q">Search by title, author, or category</label>
        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="e.g. Achebe, Fantasy, Atomic Habits">
    </div>
    <button type="submit" style="margin-top:26px;">Search</button>
</form>

<?php if ($query !== ''): ?>
    <h2 style="margin-top:30px;">Results for "<?php echo htmlspecialchars($query); ?>"</h2>

    <div class="table-responsive">
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Category</th>
            <th>Available / Total</th>
            <th>Actions</th>
        </tr>
        <?php if ($results && $results->num_rows > 0): ?>
            <?php while ($row = $results->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($row['category'] ?: '—'); ?></td>
                <td><?php echo (int)$row['available_copies']; ?> / <?php echo (int)$row['total_copies']; ?></td>
                <td class="actions">
                    <a href="books/edit.php?id=<?php echo (int)$row['book_id']; ?>">Edit</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No books matched your search.</td></tr>
        <?php endif; ?>
    </table>
    </div>
<?php endif; ?>

<?php
include 'includes/footer.php';
$conn->close();
?>
