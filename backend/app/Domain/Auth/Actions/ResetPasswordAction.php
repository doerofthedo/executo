<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class ResetPasswordAction
{
    public function execute(string $token, string $password): void
    {
        $record = DB::table('password_reset_tokens')
            ->whereNotNull('created_at')
            ->orderByDesc('created_at')
            ->get(['email', 'token'])
            ->first(static fn (object $tokenRow): bool => Hash::check($token, (string) $tokenRow->token));

        if (! is_object($record) || ! isset($record->email) || ! is_string($record->email)) {
            throw ValidationException::withMessages([
                'token' => [trans(Password::INVALID_TOKEN)],
            ]);
        }

        $user = User::query()
            ->where('email', $record->email)
            ->first();

        if ($user === null || Password::broker()->tokenExists($user, $token) === false) {
            throw ValidationException::withMessages([
                'token' => [trans(Password::INVALID_TOKEN)],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $user->tokens()->delete();

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();
    }
}
