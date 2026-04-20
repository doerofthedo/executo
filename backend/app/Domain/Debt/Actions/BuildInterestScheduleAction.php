<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Domain\Debt\DTOs\InterestScheduleData;
use App\Domain\Debt\Services\InterestCalculatorService;
use App\Models\Debt;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class BuildInterestScheduleAction
{
    public function __construct(
        private InterestCalculatorService $interestCalculator,
    ) {
    }

    /**
     * @param  Collection<int, Payment>  $payments
     */
    public function execute(Debt $debt, Collection $payments, ?CarbonImmutable $asOf = null): InterestScheduleData
    {
        return $this->interestCalculator->calculateSchedule($debt, $payments, $asOf);
    }
}
