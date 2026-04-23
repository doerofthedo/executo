<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;

final readonly class StoreDistrictAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): District
    {
        return District::query()->create($payload);
    }
}
