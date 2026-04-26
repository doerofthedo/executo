<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Actions;

use App\Models\Debtor;

final readonly class DeleteDebtorAction
{
    public function execute(Debtor $debtor, bool $forceDelete): void
    {
        if ($forceDelete) {
            $debtor->forceDelete();

            return;
        }

        $debtor->delete();
    }
}
