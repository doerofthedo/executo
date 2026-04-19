<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

final readonly class PaymentAllocatorService
{
    public function allocateToPrincipal(string $amount): string
    {
        return $amount;
    }
}
