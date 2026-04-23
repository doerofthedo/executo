<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Password;

final readonly class RequestPasswordResetAction
{
    public function execute(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user !== null && ! $user->disabled) {
            Password::broker()->sendResetLink([
                'email' => $user->email,
            ]);
        }
    }
}
