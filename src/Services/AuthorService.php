<?php

declare(strict_types=1);

final class AuthorService
{
    public static function validateCreateData(array $data): array
    {
        $errors = [];

        if (trim((string)($data['first_name'] ?? '')) === '') {
            $errors[] = 'First name is required.';
        }

        if (trim((string)($data['last_name'] ?? '')) === '') {
            $errors[] = 'Last name is required.';
        }

        return $errors;
    }
}
