-- ============================================
-- Users table — Authentication
-- Run this against the existing library_db
-- ============================================

USE library_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- No default administrator is seeded. Create the first administrator
-- using scripts/create_admin.php from the command line (see README).
