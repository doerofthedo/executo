<?php

declare(strict_types=1);

namespace App\Domain\Debt\Actions;

use App\Models\Debtor;
use App\Models\Debt;
use App\Models\District;

final readonly class CreateDebtAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(District $district, Debtor $debtor, array $payload): Debt
    {
        return Debt::query()->create([
            ...$payload,
            'district_id' => $district->id,
            'debtor_id' => $debtor->id,
        ]);
    }
}
