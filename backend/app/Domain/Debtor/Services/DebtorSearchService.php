<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Services;

final readonly class DebtorSearchService
{
    public function normaliseQuery(string $query): string
    {
        return trim(mb_strtolower($query));
    }
}
