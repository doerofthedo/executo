<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

final readonly class MfaProvisioningService
{
    public function issuer(): string
    {
        return 'Executo';
    }
}
