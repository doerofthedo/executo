<?php

declare(strict_types=1);

namespace App\Domain\Customer\Services;

final readonly class CustomerSearchService
{
    public function normaliseQuery(string $query): string
    {
        return trim(mb_strtolower($query));
    }
}
