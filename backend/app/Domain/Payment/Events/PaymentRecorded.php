<?php

declare(strict_types=1);

namespace App\Domain\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class PaymentRecorded
{
    use Dispatchable;

    public function __construct(
        public readonly string $paymentUlid,
    ) {
    }
}
