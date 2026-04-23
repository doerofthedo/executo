<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;

final readonly class VerifyEmailAction
{
    public function execute(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
