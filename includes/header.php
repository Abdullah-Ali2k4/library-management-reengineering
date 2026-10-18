<?php
/**
 * Shared Header — Dark Sidebar Navigation
 * -----------------------------------------------
 * Include this AFTER db.php, auth.php + requireLogin(), and any
 * page-specific PHP logic, but BEFORE your HTML content.
 *
 * From project root:      include 'includes/header.php';
 * From a subfolder:       include '../includes/header.php';
 */

require_once __DIR__ . '/../config/paths.php';

$current_page = basename($_SERVER['PHP_SELF']);
$current_folder = basename(dirname($_SERVER['PHP_SELF']));

function nav_active($page, $folder = null) {
    global $current_page, $current_folder;
    if ($folder !== null) {
        return ($current_folder === $folder) ? 'active' : '';
    }
    return ($current_page === $page) ? 'active' : '';
}

$loggedInUser = function_exists('currentUser') ? currentUser() : null;
$displayName = $loggedInUser['username'] ?? 'Guest';
$displayRole = $loggedInUser['role'] ?? '';
$initial = strtoupper(substr($displayName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>

<!-- Slim mobile topbar: just the menu toggle -->
<header class="topbar">
    <button id="sidebarToggle" class="hamburger-btn" aria-label="Toggle menu">
        <i class="fa-solid fa-bars"></i>
    </button>
    <div class="topbar-logo">
        <i class="fa-solid fa-book-open"></i>
        <span>Library Management System</span>
    </div>
</header>

<div id="sidebarOverlay" class="sidebar-overlay"></div>

<div class="layout">

    <aside class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <span class="sidebar-brand-icon"><i class="fa-solid fa-book-open"></i></span>
            <div>
                <div class="sidebar-brand-name">Library</div>
                <div class="sidebar-brand-sub">Management System</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="<?php echo nav_active('index.php'); ?>">
                    <a href="<?php echo BASE_URL; ?>index.php"><i class="fa-solid fa-grip"></i> Dashboard</a>
                </li>
                <li class="<?php echo nav_active(null, 'books'); ?>">
                    <a href="<?php echo BASE_URL; ?>books/view.php"><i class="fa-solid fa-book-open"></i> Books</a>
                </li>
                <li class="<?php echo nav_active(null, 'authors'); ?>">
                    <a href="<?php echo BASE_URL; ?>authors/view.php"><i class="fa-solid fa-user"></i> Authors</a>
                </li>
                <li class="<?php echo nav_active(null, 'members'); ?>">
                    <a href="<?php echo BASE_URL; ?>members/view.php"><i class="fa-solid fa-users"></i> Members</a>
                </li>
                <li class="<?php echo nav_active('issue.php'); ?>">
                    <a href="<?php echo BASE_URL; ?>issue_return/issue.php"><i class="fa-solid fa-arrow-right-arrow-left"></i> Issue Book</a>
                </li>
                <li class="<?php echo nav_active('return.php'); ?>">
                    <a href="<?php echo BASE_URL; ?>issue_return/return.php"><i class="fa-solid fa-rotate-left"></i> Return Book</a>
                </li>
                <li class="<?php echo nav_active('status.php'); ?>">
                    <a href="<?php echo BASE_URL; ?>issue_return/status.php"><i class="fa-regular fa-clock"></i> Issued/Overdue</a>
                </li>
                <li class="<?php echo nav_active('search.php'); ?>">
                    <a href="<?php echo BASE_URL; ?>search.php"><i class="fa-solid fa-magnifying-glass"></i> Search</a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-user">
            <div class="sidebar-user-info">
                <span class="sidebar-avatar"><?php echo htmlspecialchars($initial); ?></span>
                <div>
                    <div class="sidebar-user-label">Welcome,</div>
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="sidebar-user-label"><?php echo htmlspecialchars(ucfirst($displayRole), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
            <form method="POST" action="<?php echo BASE_URL; ?>auth/logout.php" class="sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="sidebar-logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
            </form>
        </div>

    </aside>

    <div class="main-column">
        <main class="site-content">
