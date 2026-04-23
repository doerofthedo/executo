<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;

final readonly class CreatePaymentAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(Customer $customer, Debt $debt, array $payload): Payment
    {
        return Payment::query()->create([
            ...$payload,
            'customer_id' => $customer->id,
            'debt_id' => $debt->id,
        ]);
    }
}
