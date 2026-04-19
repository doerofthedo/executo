<?php

declare(strict_types=1);

namespace App\Domain\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserAuthenticated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $userUlid,
    ) {
    }
}
