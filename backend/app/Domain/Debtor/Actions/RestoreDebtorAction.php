<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Actions;

use App\Models\Debtor;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class RestoreDebtorAction
{
    public function execute(Debtor $debtor): Debtor
    {
        $debtor->restore();

        $freshDebtor = $debtor->fresh();

        if ($freshDebtor === null) {
            throw new NotFoundHttpException();
        }

        return $freshDebtor;
    }
}
