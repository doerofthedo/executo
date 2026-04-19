<?php

declare(strict_types=1);

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\DTOs\PaymentData;

final readonly class RecordPaymentAction
{
    public function execute(PaymentData $data): PaymentData
    {
        return $data;
    }
}
