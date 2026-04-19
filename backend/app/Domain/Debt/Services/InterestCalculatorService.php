<?php

declare(strict_types=1);

namespace App\Domain\Debt\Services;

use App\Domain\Debt\DTOs\InterestBreakdownData;

final readonly class InterestCalculatorService
{
    public function calculateSimple(string $principal, string $annualRate, int $days): InterestBreakdownData
    {
        $dayFactor = bcdiv((string) $days, '365', 8);
        $rateFactor = bcdiv($annualRate, '100', 8);
        $interest = bcmul(bcmul($principal, $rateFactor, 8), $dayFactor, 4);
        $total = bcadd($principal, $interest, 4);

        return new InterestBreakdownData(
            principal: $principal,
            interest: $interest,
            total: $total,
            periods: ["days:$days"],
        );
    }
}
