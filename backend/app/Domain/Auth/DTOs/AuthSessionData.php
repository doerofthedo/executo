<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use App\Models\User;

final readonly class AuthSessionData
{
    public function __construct(
        public User $user,
        public string $token,
    ) {
    }
}
