<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

// Fetch authors for the dropdown
$authors_result = $conn->query("SELECT author_id, first_name, last_name FROM authors ORDER BY first_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    // --- Collect + trim input ---
    $title            = trim($_POST['title'] ?? '');
    $author_id        = trim($_POST['author_id'] ?? '');
    $isbn             = trim($_POST['isbn'] ?? '');
    $category         = trim($_POST['category'] ?? '');
    $total_copies     = trim($_POST['total_copies'] ?? '');

    // --- Server-side validation ---
    if ($title === '') {
        $errors[] = "Title is required.";
    }
    if ($author_id === '') {
        $errors[] = "Please select an author.";
    }
    if ($total_copies === '' || !is_numeric($total_copies) || (int)$total_copies < 1) {
        $errors[] = "Total copies must be a number of at least 1.";
    }
    if ($isbn !== '') {
        // Check ISBN uniqueness
        $check = $conn->prepare("SELECT book_id FROM books WHERE isbn = ?");
        $check->bind_param("s", $isbn);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "A book with this ISBN already exists.";
        }
        $check->close();
    }

    // --- Insert if valid ---
    if (empty($errors)) {
        $total_copies = (int)$total_copies;
        $available_copies = $total_copies; // new book starts fully available

        $stmt = $conn->prepare("INSERT INTO books (title, author_id, isbn, category, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssi", $title, $author_id, $isbn, $category, $total_copies, $available_copies);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong while saving. Please try again.";
        }
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<h1>Add New Book</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Book added successfully!
        <a href="view.php">View all books</a>
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

<form method="POST" action="add.php" class="validate-form">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" required
           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">

    <label for="author_id">Author</label>
    <select id="author_id" name="author_id" required>
        <option value="">-- Select Author --</option>
        <?php while ($author = $authors_result->fetch_assoc()): ?>
            <option value="<?php echo $author['author_id']; ?>"
                <?php echo (isset($author_id) && $author_id == $author['author_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label for="isbn">ISBN</label>
    <input type="text" id="isbn" name="isbn"
           value="<?php echo isset($isbn) ? htmlspecialchars($isbn) : ''; ?>">

    <label for="category">Category</label>
    <input type="text" id="category" name="category"
           value="<?php echo isset($category) ? htmlspecialchars($category) : ''; ?>">

    <label for="total_copies">Total Copies</label>
    <input type="number" id="total_copies" name="total_copies" min="1" required
           value="<?php echo isset($total_copies) ? htmlspecialchars($total_copies) : '1'; ?>">

    <button type="submit">Add Book</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>