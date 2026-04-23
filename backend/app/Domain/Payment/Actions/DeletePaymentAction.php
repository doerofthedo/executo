<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Models\Payment;

final readonly class DeletePaymentAction
{
    public function execute(Payment $payment): void
    {
        $payment->delete();
    }
}
