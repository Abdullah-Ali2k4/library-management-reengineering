<?php
/**
 * ============================================================
 * BACKEND LOGIC
 * ============================================================
 */
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requirePost(); requireCsrf();
    $username         = trim((string)($_POST['username'] ?? ''));
    $email            = trim((string)($_POST['email'] ?? ''));
    $password         = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    if ($username === '' || strlen($username) > 50 || $email === '' || $password === '') {
        $errors[] = "All fields are required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (strlen($password) < 10) {
        $errors[] = "Password must be at least 10 characters.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "That username or email is already registered.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'staff';

        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $password_hash, $role);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!-- ============================================================
     FRONTEND placeholder — restyle freely. The PHP above only
     needs name="username", name="email", name="password",
     name="confirm_password" and posting to register.php.
     ============================================================ -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body style="display:flex; align-items:center; justify-content:center; min-height:100vh; background:#f7f9fb;">

<div style="width:100%; max-width:380px; padding:20px;">
    <div style="text-align:center; margin-bottom:24px;">
        <i class="fa-solid fa-book-open" style="font-size:1.8rem; color:#2563eb;"></i>
        <h1 style="font-size:1.4rem; margin-top:10px;">Create an Account</h1>
    </div>

    <?php if ($success): ?>
        <p style="color:green; font-weight:bold; text-align:center;">
            Account created! <a href="index.php">Log in now</a>
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

    <?php if (!$success): ?>
    <form method="POST" action="register.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required
               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit" style="width:100%;">Register</button>
    </form>
    <?php endif; ?>

    <p style="text-align:center; margin-top:16px; font-size:0.85rem; color:#64748b;">
        Already have an account? <a href="index.php">Log in</a>
    </p>
</div>

</body>
</html>
