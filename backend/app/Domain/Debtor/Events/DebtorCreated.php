<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class DebtorCreated
{
    use Dispatchable;

    public function __construct(
        public readonly string $debtorUlid,
    ) {}
}
