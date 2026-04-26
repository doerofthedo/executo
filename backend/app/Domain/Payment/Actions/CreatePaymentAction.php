<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Models\Debtor;
use App\Models\Debt;
use App\Models\Payment;

final readonly class CreatePaymentAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(Debtor $debtor, Debt $debt, array $payload): Payment
    {
        return Payment::query()->create([
            ...$payload,
            'debtor_id' => $debtor->id,
            'debt_id' => $debt->id,
        ]);
    }
}
