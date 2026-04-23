<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Models\Debt;

final readonly class DeleteDebtAction
{
    public function execute(Debt $debt): void
    {
        $debt->delete();
    }
}
