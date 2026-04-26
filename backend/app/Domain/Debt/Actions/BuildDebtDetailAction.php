<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Domain\Debt\DTOs\InterestScheduleData;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final readonly class BuildDebtDetailAction
{
    public function __construct(
        private BuildInterestScheduleAction $buildInterestSchedule,
    ) {}

    public function execute(Debt $debt): InterestScheduleData
    {
        $debt->load(['district', 'debtor', 'payments.debtor', 'payments.debt']);
        /** @var EloquentCollection<int, Payment> $payments */
        $payments = $debt->payments;

        return $this->buildInterestSchedule->execute($debt, $payments);
    }
}
