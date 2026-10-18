<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if ($full_name === '') {
        $errors[] = "Full name is required.";
    }
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check email uniqueness
        $check = $conn->prepare("SELECT member_id FROM members WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "A member with this email already exists.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO members (full_name, email, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $full_name, $email, $phone);

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

<h1>Add New Member</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Member added successfully!
        <a href="view.php">View all members</a>
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
    <label for="full_name">Full Name</label>
    <input type="text" id="full_name" name="full_name" required
           value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required
           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone"
           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">

    <button type="submit">Add Member</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>