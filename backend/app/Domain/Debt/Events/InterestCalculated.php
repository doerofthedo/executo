<?php

declare(strict_types=1);

namespace App\Domain\Debt\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class InterestCalculated
{
    use Dispatchable;

    public function __construct(
        public readonly string $debtUlid,
    ) {
    }
}
