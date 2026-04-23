<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListPaymentsAction
{
    /**
     * @return Collection<int, Payment>
     */
    public function execute(Debt $debt): Collection
    {
        return $debt->payments()
            ->with(['customer', 'debt'])
            ->orderByDesc('date')
            ->get();
    }
}
