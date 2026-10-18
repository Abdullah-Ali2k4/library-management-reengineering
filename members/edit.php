<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin', 'staff');

require_once '../config/db.php';

$errors = [];
$success = false;

$member_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0);

if ($member_id <= 0) {
    die("Invalid member ID.");
}

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
        // Check email uniqueness, excluding this member itself
        $check = $conn->prepare("SELECT member_id FROM members WHERE email = ? AND member_id != ?");
        $check->bind_param("si", $email, $member_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "Another member already uses this email.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ? WHERE member_id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $phone, $member_id);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong while updating. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch current member data to pre-fill the form
$stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$member) {
    die("Member not found.");
}

$full_name = $_POST['full_name'] ?? $member['full_name'];
$email     = $_POST['email']     ?? $member['email'];
$phone     = $_POST['phone']     ?? $member['phone'];

include '../includes/header.php';
?>

<h1>Edit Member</h1>

<?php if ($success): ?>
    <p style="color: green; font-weight: bold;">Member updated successfully!
        <a href="view.php">Back to all members</a>
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

<form method="POST" action="edit.php?id=<?php echo $member_id; ?>" class="validate-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="member_id" value="<?php echo $member_id; ?>">

    <label for="full_name">Full Name</label>
    <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($full_name); ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">

    <button type="submit">Save Changes</button>
</form>

<?php
include '../includes/footer.php';
$conn->close();
?>