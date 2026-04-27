<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Debt\DTOs\InterestScheduleData;
use App\Domain\Debt\DTOs\InterestScheduleRowData;
use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DebtDetailResource extends JsonResource
{
    public function __construct(
        Debt $resource,
        private readonly InterestScheduleData $interestSchedule,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'debt' => (new DebtResource($this->resource->loadMissing(['district', 'debtor'])))->resolve($request),
            'payments' => PaymentResource::collection($this->resource->payments->loadMissing(['debtor', 'debt']))->resolve($request),
            'interest' => [
                'columns' => [
                    ['key' => 'payment_date', 'label_key' => 'debt.interest_breakdown.columns.payment_date', 'align' => 'left'],
                    ['key' => 'payment_amount', 'label_key' => 'debt.interest_breakdown.columns.payment_amount', 'align' => 'right'],
                    ['key' => 'days_since_last_payment', 'label_key' => 'debt.interest_breakdown.columns.days_since_last_payment', 'align' => 'right'],
                    ['key' => 'interest_per_day', 'label_key' => 'debt.interest_breakdown.columns.interest_per_day', 'align' => 'right'],
                    ['key' => 'interest_accrued', 'label_key' => 'debt.interest_breakdown.columns.interest_accrued', 'align' => 'right'],
                    ['key' => 'interest_rollover', 'label_key' => 'debt.interest_breakdown.columns.interest_rollover', 'align' => 'right'],
                    ['key' => 'interest_total', 'label_key' => 'debt.interest_breakdown.columns.interest_total', 'align' => 'right'],
                    ['key' => 'interest_paid', 'label_key' => 'debt.interest_breakdown.columns.interest_paid', 'align' => 'right'],
                    ['key' => 'principal_paid', 'label_key' => 'debt.interest_breakdown.columns.principal_paid', 'align' => 'right'],
                    ['key' => 'remaining_interest', 'label_key' => 'debt.interest_breakdown.columns.remaining_interest', 'align' => 'right'],
                    ['key' => 'remaining_principal', 'label_key' => 'debt.interest_breakdown.columns.remaining_principal', 'align' => 'right'],
                    ['key' => 'total_debt', 'label_key' => 'debt.interest_breakdown.columns.total_debt', 'align' => 'right'],
                ],
                'rows' => array_map(
                    fn(InterestScheduleRowData $row): array => $this->formatRow($row),
                    $this->interestSchedule->rows,
                ),
                'total_row' => $this->formatRow($this->interestSchedule->totalRow),
            ],
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function formatRow(InterestScheduleRowData $row): array
    {
        return [
            'payment_date' => $row->paymentDate,
            'payment_ulid' => $row->paymentUlid,
            'payment_amount' => $row->paymentAmount,
            'days_since_last_payment' => $row->daysSinceLastPayment,
            'interest_per_day' => $row->interestPerDay,
            'interest_accrued' => $row->interestAccrued,
            'interest_rollover' => $row->interestRollover,
            'interest_total' => $row->interestTotal,
            'interest_paid' => $row->interestPaid,
            'principal_paid' => $row->principalPaid,
            'remaining_interest' => $row->remainingInterest,
            'remaining_principal' => $row->remainingPrincipal,
            'total_debt' => $row->totalDebt,
            'row_class' => $row->rowClass,
        ];
    }
}
