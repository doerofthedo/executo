<?php

declare(strict_types=1);

namespace App\Domain\Debt\DTOs;

use Spatie\LaravelData\Data;

final class InterestScheduleRowData extends Data
{
    public function __construct(
        public string $paymentDate,
        public ?string $paymentAmount,
        public ?int $daysSinceLastPayment,
        public ?string $interestPerDay,
        public ?string $interestAccrued,
        public ?string $interestRollover,
        public ?string $interestTotal,
        public ?string $interestPaid,
        public ?string $principalPaid,
        public ?string $remainingInterest,
        public ?string $remainingPrincipal,
        public string $totalDebt,
        public string $rowClass,
    ) {
    }
}
