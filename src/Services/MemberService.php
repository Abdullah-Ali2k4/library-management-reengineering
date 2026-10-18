<?php

declare(strict_types=1);

final class MemberService
{
    public static function validateCreateData(array $data): array
    {
        $errors = [];

        if (trim((string)($data['full_name'] ?? '')) === '') {
            $errors[] = 'Full name is required.';
        }

        $email = trim((string)($data['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        return $errors;
    }
}
