<?php

declare(strict_types=1);

final class Database
{
    private static ?Database $instance = null;

    private mysqli $connection;

    private function __construct()
    {
        $dbHost = getenv('DB_HOST') ?: 'localhost';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $dbName = getenv('DB_NAME') ?: 'library_db';

        mysqli_report(MYSQLI_REPORT_OFF);

        $this->connection = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

        if ($this->connection->connect_errno) {
            error_log('Library DB connection failed (' . $this->connection->connect_errno . '): ' . $this->connection->connect_error);
            http_response_code(500);
            exit('The application is temporarily unavailable. Please try again later.');
        }

        if (!$this->connection->set_charset('utf8mb4')) {
            error_log('Library DB charset configuration failed: ' . $this->connection->error);
            http_response_code(500);
            exit('The application is temporarily unavailable. Please try again later.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}
