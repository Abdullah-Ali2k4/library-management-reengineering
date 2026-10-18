<?php

declare(strict_types=1);

final class InventoryService
{
    public static function totalCopiesMustBeValid(int $totalCopies): bool
    {
        return $totalCopies >= 1;
    }

    public static function availableCopiesMustBeValid(int $availableCopies, int $totalCopies): bool
    {
        return $availableCopies >= 0 && $availableCopies <= $totalCopies;
    }

    public static function inventoryInvariantHolds(int $totalCopies, int $availableCopies, int $activeLoans): bool
    {
        return $totalCopies === ($availableCopies + $activeLoans);
    }

    public static function activeLoansForBook(mysqli $conn, int $bookId): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) AS n FROM issued_books WHERE book_id = ? AND status = 'issued'");
        $stmt->bind_param('i', $bookId);
        $stmt->execute();
        $count = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();

        return $count;
    }
}
