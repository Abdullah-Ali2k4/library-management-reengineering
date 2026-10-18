<?php

declare(strict_types=1);

final class BookService
{
    public static function validateCreateData(array $data): array
    {
        $errors = [];

        $title = trim((string)($data['title'] ?? ''));
        $authorId = (int)($data['author_id'] ?? 0);
        $totalCopies = (int)($data['total_copies'] ?? 0);

        if ($title === '') {
            $errors[] = 'Title is required.';
        }

        if ($authorId <= 0) {
            $errors[] = 'A valid author is required.';
        }

        if (!InventoryService::totalCopiesMustBeValid($totalCopies)) {
            $errors[] = 'Total copies must be at least 1.';
        }

        return $errors;
    }

    public static function validateUpdateData(array $data, int $currentTotal, int $currentAvailable, int $activeLoans): array
    {
        $errors = [];
        $newTotal = (int)($data['total_copies'] ?? $currentTotal);

        if (!InventoryService::totalCopiesMustBeValid($newTotal)) {
            $errors[] = 'Total copies must be at least 1.';
        }

        $newAvailable = (int)($data['available_copies'] ?? $currentAvailable);
        if (!InventoryService::availableCopiesMustBeValid($newAvailable, $newTotal)) {
            $errors[] = 'Available copies must be between 0 and total copies.';
        }

        if ($newTotal < $activeLoans) {
            $errors[] = 'Total copies cannot be reduced below the number of active loans.';
        }

        return $errors;
    }
}
