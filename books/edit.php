<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');
require_once __DIR__ . '/../src/Services/BookService.php';
require_once __DIR__ . '/../src/Services/InventoryService.php';

require_once '../config/db.php';

$errors = [];
$success = false;

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0);

if ($book_id <= 0) {
    die("Invalid book ID.");
}

$authors_result = $conn->query("SELECT author_id, first_name, last_name FROM authors ORDER BY first_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $title = trim((string)($_POST['title'] ?? ''));
    $author_id = trim((string)($_POST['author_id'] ?? ''));
    $isbn = trim((string)($_POST['isbn'] ?? ''));
    $category = trim((string)($_POST['category'] ?? ''));
    $total_copies = trim((string)($_POST['total_copies'] ?? ''));

    $stmt = $conn->prepare("SELECT total_copies, available_copies FROM books WHERE book_id = ?");
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book) {
        die("Book not found.");
    }

    $errors = BookService::validateUpdateData([
        'title' => $title,
        'author_id' => $author_id,
        'total_copies' => $total_copies,
        'available_copies' => (int)($book['available_copies'] ?? 0),
    ], (int)$book['total_copies'], (int)$book['available_copies'], InventoryService::activeLoansForBook($conn, $book_id));

    if ($isbn !== '') {
        $check = $conn->prepare("SELECT book_id FROM books WHERE isbn = ? AND book_id != ?");
        $check->bind_param("si", $isbn, $book_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "Another book already uses this ISBN.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $total_copies = (int)$total_copies;
        $available_copies = (int)$book['available_copies'];

        $stmt = $conn->prepare("UPDATE books SET title = ?, author_id = ?, isbn = ?, category = ?, total_copies = ?, available_copies = ? WHERE book_id = ?");
        $stmt->bind_param("sisssii", $title, $author_id, $isbn, $category, $total_copies, $available_copies, $book_id);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong while updating. Please try again.";
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    die("Book not found.");
}

$title = $_POST['title'] ?? $book['title'];
$author_id = $_POST['author_id'] ?? $book['author_id'];
$isbn = $_POST['isbn'] ?? $book['isbn'];
$category = $_POST['category'] ?? $book['category'];
$total_copies = $_POST['total_copies'] ?? $book['total_copies'];
$available_copies = $book['available_copies'];

include '../includes/header.php';
?>

<h1>Edit Book</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Book updated successfully!
        <a href="view.php">Back to all books</a>
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

<form method="POST" action="edit.php?id=<?php echo $book_id; ?>" class="validate-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">

    <label for="title">Title</label>
    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($title); ?>">

    <label for="author_id">Author</label>
    <select id="author_id" name="author_id" required>
        <option value="">-- Select Author --</option>
        <?php while ($author = $authors_result->fetch_assoc()): ?>
            <option value="<?php echo $author['author_id']; ?>"
                <?php echo ($author_id == $author['author_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="isbn">ISBN</label>
    <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">

    <label for="category">Category</label>
    <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>">

    <label for="total_copies">Total Copies</label>
    <input type="number" id="total_copies" name="total_copies" min="1" required
           value="<?php echo htmlspecialchars($total_copies); ?>">

    <p style="margin: 0.8rem 0; color: #475569;">Available copies: <strong><?php echo (int)$available_copies; ?></strong> (managed automatically)</p>

    <button type="submit">Save Changes</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>