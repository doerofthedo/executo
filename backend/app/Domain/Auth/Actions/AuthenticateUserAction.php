<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\AuthSessionData;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class AuthenticateUserAction
{
    /**
     * @param  array{login: string, password: string}  $credentials
     */
    public function execute(array $credentials, Request $request): AuthSessionData
    {
        $rawLogin = $credentials['login'];
        $password = $credentials['password'];
        $login = $rawLogin === 'admin'
            ? 'admin@executo.local'
            : $rawLogin;

        $user = User::query()
            ->where('email', $login)
            ->first();

        $invalidCredentials = $user === null
            || ! Hash::check($password, $user->password)
            || $user->disabled
            || ! $user->hasVerifiedEmail();

        if ($invalidCredentials) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are invalid.'],
            ]);
        }

        $user->tokens()->delete();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $expiration = config('sanctum.expiration');
        $expiresAt = is_int($expiration) ? now()->addMinutes($expiration) : null;
        $token = $user->createToken('spa', ['*'], $expiresAt)->plainTextToken;

        return new AuthSessionData($user, $token);
    }
}
