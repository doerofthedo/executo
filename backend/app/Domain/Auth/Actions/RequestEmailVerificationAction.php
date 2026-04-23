<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;

final readonly class RequestEmailVerificationAction
{
    public function execute(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }
}
