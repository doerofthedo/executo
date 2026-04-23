<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;

final readonly class CreateDebtAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(District $district, Customer $customer, array $payload): Debt
    {
        return Debt::query()->create([
            ...$payload,
            'district_id' => $district->id,
            'customer_id' => $customer->id,
        ]);
    }
}
