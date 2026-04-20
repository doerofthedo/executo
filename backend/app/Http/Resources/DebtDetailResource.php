<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Debt\DTOs\InterestScheduleData;
use App\Domain\Debt\DTOs\InterestScheduleRowData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DebtDetailResource extends JsonResource
{
    public function __construct(
        mixed $resource,
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
            'debt' => (new DebtResource($this->resource->loadMissing(['district', 'customer'])))->resolve($request),
            'payments' => PaymentResource::collection($this->resource->payments->loadMissing(['customer', 'debt']))->resolve($request),
            'interest' => [
                'columns' => [
                    ['key' => 'payment_date', 'label' => 'Date', 'align' => 'left'],
                    ['key' => 'payment_amount', 'label' => 'Payment Amount', 'align' => 'right'],
                    ['key' => 'days_since_last_payment', 'label' => 'Days Since Last Payment', 'align' => 'right'],
                    ['key' => 'interest_per_day', 'label' => 'Interest Per Day', 'align' => 'right'],
                    ['key' => 'interest_accrued', 'label' => 'Interest Accrued', 'align' => 'right'],
                    ['key' => 'interest_rollover', 'label' => 'Interest Rollover', 'align' => 'right'],
                    ['key' => 'interest_total', 'label' => 'Interest Total', 'align' => 'right'],
                    ['key' => 'interest_paid', 'label' => 'Interest Paid', 'align' => 'right'],
                    ['key' => 'principal_paid', 'label' => 'Principal Paid', 'align' => 'right'],
                    ['key' => 'remaining_interest', 'label' => 'Remaining Interest', 'align' => 'right'],
                    ['key' => 'remaining_principal', 'label' => 'Remaining Principal', 'align' => 'right'],
                    ['key' => 'total_debt', 'label' => 'Total Debt', 'align' => 'right'],
                ],
                'rows' => array_map(
                    fn (InterestScheduleRowData $row): array => $this->formatRow($row),
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
