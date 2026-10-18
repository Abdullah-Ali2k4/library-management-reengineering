<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

$author_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0);

if ($author_id <= 0) {
    die("Invalid author ID.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');

    if ($first_name === '') {
        $errors[] = "First name is required.";
    }
    if ($last_name === '') {
        $errors[] = "Last name is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE authors SET first_name = ?, last_name = ?, bio = ? WHERE author_id = ?");
        $stmt->bind_param("sssi", $first_name, $last_name, $bio, $author_id);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong while updating. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch current author data to pre-fill the form
$stmt = $conn->prepare("SELECT * FROM authors WHERE author_id = ?");
$stmt->bind_param("i", $author_id);
$stmt->execute();
$author = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$author) {
    die("Author not found.");
}

$first_name = $_POST['first_name'] ?? $author['first_name'];
$last_name  = $_POST['last_name']  ?? $author['last_name'];
$bio        = $_POST['bio']        ?? $author['bio'];

include '../includes/header.php';
?>

<h1>Edit Author</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Author updated successfully!
        <a href="view.php">Back to all authors</a>
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

<form method="POST" action="edit.php?id=<?php echo $author_id; ?>
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">" class="validate-form">
    <input type="hidden" name="author_id" value="<?php echo $author_id; ?>">

    <label for="first_name">First Name</label>
    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($first_name); ?>">

    <label for="last_name">Last Name</label>
    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($last_name); ?>">

    <label for="bio">Bio</label>
    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>

    <button type="submit">Save Changes</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>