<?php

declare(strict_types=1);

namespace App\Domain\District\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class DistrictCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $districtUlid,
    ) {
    }
}
