<?php

declare(strict_types=1);

namespace App\Domain\District\DTOs;

use Spatie\LaravelData\Data;

final class DistrictData extends Data
{
    public function __construct(
        public int $number,
        public int $ownerId,
    ) {
    }
}
