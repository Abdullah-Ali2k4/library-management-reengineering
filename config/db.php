<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Database/Database.php';

$conn = Database::getInstance()->getConnection();
