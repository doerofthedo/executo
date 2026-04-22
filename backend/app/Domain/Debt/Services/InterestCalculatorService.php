<?php

declare(strict_types=1);

namespace App\Domain\Debt\Services;

use App\Domain\Debt\DTOs\InterestBreakdownData;
use App\Domain\Debt\DTOs\InterestScheduleData;
use App\Domain\Debt\DTOs\InterestScheduleRowData;
use App\Models\Debt;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class InterestCalculatorService
{
    private const ANNUAL_RATE = '6.0000';

    /**
     * @param  numeric-string  $principal
     * @param  numeric-string  $annualRate
     */
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

    /**
     * @param  Collection<int, Payment>  $payments
     */
    public function calculateSchedule(Debt $debt, Collection $payments, ?CarbonImmutable $asOf = null): InterestScheduleData
    {
        $asOfDate = ($asOf ?? CarbonImmutable::now())->startOfDay();
        $debtDate = CarbonImmutable::parse($debt->date)->startOfDay();

        $rows = [];
        $remainingPrincipal = $this->normalizeMoney((string) $debt->amount);
        $remainingInterest = '0.00';
        $rolloverStorage = '0.00';
        $totalInterestAccrued = '0.00';
        $totalPayments = '0.00';
        $totalInterestPaid = '0.00';
        $totalPrincipalPaid = '0.00';
        $lastPaymentDate = $debtDate;

        $rows[] = new InterestScheduleRowData(
            paymentDate: $debtDate->toDateString(),
            paymentAmount: null,
            daysSinceLastPayment: null,
            interestPerDay: null,
            interestAccrued: null,
            interestRollover: null,
            interestTotal: null,
            interestPaid: null,
            principalPaid: null,
            remainingInterest: null,
            remainingPrincipal: $this->formatScheduleMoney($remainingPrincipal),
            totalDebt: $this->formatScheduleMoney($remainingPrincipal),
            rowClass: 'opening',
        );

        foreach ($payments->sortBy('date')->values() as $payment) {
            $paymentDate = CarbonImmutable::parse($payment->date)->startOfDay();
            $daysSinceLastPayment = (int) $lastPaymentDate->diffInDays($paymentDate);
            $interestPerDay = $this->dailyInterest($remainingPrincipal);
            $interestAccrued = $this->roundScheduleMoney(
                bcmul($interestPerDay, (string) $daysSinceLastPayment, 8),
            );
            $interestRollover = $rolloverStorage;
            $interestTotal = $this->roundScheduleMoney(
                bcadd($interestAccrued, $interestRollover, 8),
            );
            $paymentAmount = $this->normalizeMoney((string) $payment->amount);
            $interestPaid = $this->min($paymentAmount, $interestTotal);
            $principalPaid = $this->max(
                bcsub($paymentAmount, $interestTotal, 8),
                '0.00',
            );
            $remainingInterest = $this->max(
                bcsub($interestTotal, $paymentAmount, 8),
                '0.00',
            );

            $rolloverStorage = $remainingInterest;
            $remainingPrincipal = $this->max(
                bcsub($remainingPrincipal, $principalPaid, 8),
                '0.0000',
            );
            $lastPaymentDate = $paymentDate;
            $totalInterestAccrued = $this->roundScheduleMoney(
                bcadd($totalInterestAccrued, $interestAccrued, 8),
            );
            $totalPayments = $this->roundScheduleMoney(
                bcadd($totalPayments, $paymentAmount, 8),
            );
            $totalInterestPaid = $this->roundScheduleMoney(
                bcadd($totalInterestPaid, $interestPaid, 8),
            );
            $totalPrincipalPaid = $this->roundScheduleMoney(
                bcadd($totalPrincipalPaid, $principalPaid, 8),
            );

            $rows[] = new InterestScheduleRowData(
                paymentDate: $paymentDate->toDateString(),
                paymentAmount: $this->formatScheduleMoney($paymentAmount),
                daysSinceLastPayment: $daysSinceLastPayment,
                interestPerDay: $interestPerDay,
                interestAccrued: $interestAccrued,
                interestRollover: $interestRollover,
                interestTotal: $interestTotal,
                interestPaid: $interestPaid,
                principalPaid: $this->formatScheduleMoney($principalPaid),
                remainingInterest: $remainingInterest,
                remainingPrincipal: $this->formatScheduleMoney($remainingPrincipal),
                totalDebt: $this->roundScheduleMoney(
                    bcadd($remainingPrincipal, $remainingInterest, 8),
                ),
                rowClass: 'payment',
            );
        }

        $daysSinceLastPayment = (int) $lastPaymentDate->diffInDays($asOfDate);
        $interestPerDay = $this->dailyInterest($remainingPrincipal);
        $interestAccrued = $this->roundScheduleMoney(
            bcmul($interestPerDay, (string) $daysSinceLastPayment, 8),
        );
        $interestRollover = $rolloverStorage;
        $interestTotal = $this->roundScheduleMoney(
            bcadd($interestAccrued, $interestRollover, 8),
        );
        $remainingInterest = $interestTotal;
        $totalInterestAccrued = $this->roundScheduleMoney(
            bcadd($totalInterestAccrued, $interestAccrued, 8),
        );

        $rows[] = new InterestScheduleRowData(
            paymentDate: $asOfDate->toDateString(),
            paymentAmount: null,
            daysSinceLastPayment: $daysSinceLastPayment,
            interestPerDay: $interestPerDay,
            interestAccrued: $interestAccrued,
            interestRollover: $interestRollover,
            interestTotal: $interestTotal,
            interestPaid: null,
            principalPaid: null,
            remainingInterest: $remainingInterest,
            remainingPrincipal: $this->formatScheduleMoney($remainingPrincipal),
            totalDebt: $this->roundScheduleMoney(
                bcadd($remainingPrincipal, $remainingInterest, 8),
            ),
            rowClass: 'current',
        );

        return new InterestScheduleData(
            rows: $rows,
            totalRow: new InterestScheduleRowData(
                paymentDate: 'Total',
                paymentAmount: $totalPayments,
                daysSinceLastPayment: (int) $debtDate->diffInDays($asOfDate),
                interestPerDay: null,
                interestAccrued: $totalInterestAccrued,
                interestRollover: null,
                interestTotal: null,
                interestPaid: $totalInterestPaid,
                principalPaid: $totalPrincipalPaid,
                remainingInterest: $remainingInterest,
                remainingPrincipal: $this->formatScheduleMoney($remainingPrincipal),
                totalDebt: $this->roundScheduleMoney(
                    bcadd($remainingPrincipal, $remainingInterest, 8),
                ),
                rowClass: 'total',
            ),
        );
    }

    /**
     * @param  numeric-string  $remainingPrincipal
     * @return numeric-string
     */
    private function dailyInterest(string $remainingPrincipal): string
    {
        return $this->roundDisplay(
            bcdiv(
                bcmul($remainingPrincipal, self::ANNUAL_RATE, 12),
                '36500',
                12,
            ),
            6,
        );
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private function normalizeMoney(string $value): string
    {
        return $this->roundMoney($value);
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private function roundScheduleMoney(string $value): string
    {
        return $this->roundDisplay($value, 2);
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private function formatScheduleMoney(string $value): string
    {
        return $this->roundScheduleMoney($value);
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private function roundMoney(string $value): string
    {
        return $this->roundDisplay($value, 4);
    }

    /**
     * @param  numeric-string  $value
     * @return numeric-string
     */
    private function roundDisplay(string $value, int $scale): string
    {
        return bcadd($value, $this->roundingAdjustment($scale), $scale);
    }

    /**
     * @param  numeric-string  $left
     * @param  numeric-string  $right
     * @return numeric-string
     */
    private function min(string $left, string $right): string
    {
        return bccomp($left, $right, 4) <= 0 ? $left : $right;
    }

    /**
     * @param  numeric-string  $left
     * @param  numeric-string  $right
     * @return numeric-string
     */
    private function max(string $left, string $right): string
    {
        return bccomp($left, $right, 4) >= 0 ? $left : $right;
    }

    /**
     * @return numeric-string
     */
    private function roundingAdjustment(int $scale): string
    {
        $precision = max($scale, 0) + 1;

        return bcdiv('5', '1' . str_repeat('0', $precision), $precision);
    }
}
