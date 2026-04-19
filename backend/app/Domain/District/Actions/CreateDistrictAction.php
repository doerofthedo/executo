<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Domain\District\DTOs\DistrictData;

final readonly class CreateDistrictAction
{
    public function execute(DistrictData $data): DistrictData
    {
        return $data;
    }
}
