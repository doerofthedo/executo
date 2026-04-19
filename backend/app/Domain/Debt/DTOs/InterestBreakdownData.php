<?php

declare(strict_types=1);

namespace App\Domain\Debt\DTOs;

use Spatie\LaravelData\Data;

final class InterestBreakdownData extends Data
{
    /**
     * @param array<int, string> $periods
     */
    public function __construct(
        public string $principal,
        public string $interest,
        public string $total,
        public array $periods,
    ) {
    }
}
