<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Domain\Debt\DTOs\InterestBreakdownData;
use App\Domain\Debt\Services\InterestCalculatorService;

final readonly class CalculateInterestAction
{
    public function __construct(
        private InterestCalculatorService $interestCalculator,
    ) {
    }

    /**
     * @param  numeric-string  $principal
     * @param  numeric-string  $annualRate
     */
    public function execute(string $principal, string $annualRate, int $days): InterestBreakdownData
    {
        return $this->interestCalculator->calculateSimple($principal, $annualRate, $days);
    }
}
