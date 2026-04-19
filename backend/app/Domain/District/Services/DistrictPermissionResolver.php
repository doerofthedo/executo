<?php

declare(strict_types=1);

namespace App\Domain\District\Services;

final readonly class DistrictPermissionResolver
{
    /**
     * @return array<int, string>
     */
    public function resolveDefaultPermissions(): array
    {
        return [
            'district.customer.view',
            'district.debt.view',
            'district.payment.view',
        ];
    }
}
