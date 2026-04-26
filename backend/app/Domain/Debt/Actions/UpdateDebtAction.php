<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Models\Debt;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdateDebtAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(Debt $debt, array $payload): Debt
    {
        $debt->fill($payload);

        if ($debt->isDirty()) {
            $debt->save();
        }

        $freshDebt = $debt->fresh(['district', 'debtor']);

        if ($freshDebt === null) {
            throw new NotFoundHttpException();
        }

        return $freshDebt;
    }
}
