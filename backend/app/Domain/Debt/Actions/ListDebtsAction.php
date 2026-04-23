<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Models\Customer;
use App\Models\Debt;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListDebtsAction
{
    /**
     * @return Collection<int, Debt>
     */
    public function execute(Customer $customer): Collection
    {
        return $customer->debts()
            ->with(['district', 'customer'])
            ->orderByDesc('date')
            ->get();
    }
}
