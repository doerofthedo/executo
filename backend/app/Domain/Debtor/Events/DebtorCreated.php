<?php

declare(strict_types=1);

namespace App\Domain\Customer\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CustomerCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $customerUlid,
    ) {
    }
}
