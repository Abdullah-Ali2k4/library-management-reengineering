<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();
include 'includes/header.php';

$total_books   = $conn->query("SELECT COUNT(*) AS c FROM books")->fetch_assoc()['c'];
$total_authors = $conn->query("SELECT COUNT(*) AS c FROM authors")->fetch_assoc()['c'];
$total_members = $conn->query("SELECT COUNT(*) AS c FROM members")->fetch_assoc()['c'];
$total_issued  = $conn->query("SELECT COUNT(*) AS c FROM issued_books WHERE status = 'issued'")->fetch_assoc()['c'];
$total_overdue = $conn->query("SELECT COUNT(*) AS c FROM issued_books WHERE status = 'issued' AND due_date < CURDATE()")->fetch_assoc()['c'];

$user = currentUser();
?>

<section class="hero">
    <div class="hero-icon"><i class="fa-solid fa-book-open"></i></div>
    <h1>Welcome <?php echo htmlspecialchars($user['username'] ?? 'user'); ?></h1>
    <p>Manage books, authors, members, and track issued records &mdash; all in one place.</p>
</section>

<section class="stats-grid">
    <div class="stat-card stat-blue">
        <span class="stat-icon-badge"><i class="fa-solid fa-book-open"></i></span>
        <div class="stat-label">Total Books</div>
        <div class="stat-number"><?php echo $total_books; ?></div>
    </div>
    <div class="stat-card stat-teal">
        <span class="stat-icon-badge"><i class="fa-solid fa-feather-pointed"></i></span>
        <div class="stat-label">Total Authors</div>
        <div class="stat-number"><?php echo $total_authors; ?></div>
    </div>
    <div class="stat-card stat-green">
        <span class="stat-icon-badge"><i class="fa-solid fa-users"></i></span>
        <div class="stat-label">Total Members</div>
        <div class="stat-number"><?php echo $total_members; ?></div>
    </div>
    <div class="stat-card stat-orange">
        <span class="stat-icon-badge"><i class="fa-solid fa-bookmark"></i></span>
        <div class="stat-label">Books Currently Issued</div>
        <div class="stat-number"><?php echo $total_issued; ?></div>
    </div>
    <div class="stat-card stat-red">
        <span class="stat-icon-badge"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <div class="stat-label">Overdue</div>
        <div class="stat-number"><?php echo $total_overdue; ?></div>
    </div>
</section>

<section class="quick-links">
    <h2>Quick Actions</h2>
    <div class="quick-links-grid">
        <a href="books/add.php" class="quick-link">
            <span class="quick-link-icon"><i class="fa-solid fa-circle-plus"></i></span> Add New Book
        </a>
        <a href="books/view.php" class="quick-link">
            <span class="quick-link-icon"><i class="fa-solid fa-book-open"></i></span> View All Books
        </a>
        <a href="issue_return/issue.php" class="quick-link">
            <span class="quick-link-icon"><i class="fa-solid fa-arrow-right-arrow-left"></i></span> Issue a Book
        </a>
        <a href="issue_return/return.php" class="quick-link">
            <span class="quick-link-icon"><i class="fa-solid fa-rotate-left"></i></span> Return a Book
        </a>
        <a href="search.php" class="quick-link">
            <span class="quick-link-icon"><i class="fa-solid fa-magnifying-glass"></i></span> Search Books
        </a>
    </div>
</section>

<?php
include 'includes/footer.php';
$conn->close();
?>
