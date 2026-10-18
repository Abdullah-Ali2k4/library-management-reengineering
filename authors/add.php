<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

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
        $stmt = $conn->prepare("INSERT INTO authors (first_name, last_name, bio) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $first_name, $last_name, $bio);

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

<h1>Add New Author</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Author added successfully!
        <a href="view.php">View all authors</a>
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
    <label for="first_name">First Name</label>
    <input type="text" id="first_name" name="first_name" required
           value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">

    <label for="last_name">Last Name</label>
    <input type="text" id="last_name" name="last_name" required
           value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">

    <label for="bio">Bio</label>
    <textarea id="bio" name="bio" rows="4"><?php echo isset($bio) ? htmlspecialchars($bio) : ''; ?></textarea>

    <button type="submit">Add Author</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>