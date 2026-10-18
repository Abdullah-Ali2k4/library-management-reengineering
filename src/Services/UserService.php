<?php

declare(strict_types=1);

final class UserService
{
    public static function validateRegistration(array $data): array
    {
        $errors = [];

        $username = trim((string)($data['username'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if ($username === '' || strlen($username) > 50) {
            $errors[] = 'Username is required and must be 50 characters or fewer.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (strlen($password) < 10) {
            $errors[] = 'Password must be at least 10 characters.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        return $errors;
    }
}
