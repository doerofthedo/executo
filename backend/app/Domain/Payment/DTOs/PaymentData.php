<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTOs;

use Spatie\LaravelData\Data;

final class PaymentData extends Data
{
    public function __construct(
        public string $amount,
        public string $paymentDate,
        public ?string $reference,
        public int $recordedBy,
    ) {
    }
}
