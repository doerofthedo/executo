<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Models\Debtor;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListDebtsAction
{
    /**
     * @return Collection<int, Debt>
     */
    public function execute(Debtor $debtor): Collection
    {
        return $debtor->debts()
            ->with(['district', 'debtor'])
            ->orderByDesc('date')
            ->get();
    }
}
