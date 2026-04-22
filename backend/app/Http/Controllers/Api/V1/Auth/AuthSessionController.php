<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\CurrentUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthSessionController extends Controller
{
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $login = $credentials['login'] === 'admin'
            ? 'admin@executo.local'
            : $credentials['login'];

        $user = User::query()
            ->where('email', $login)
            ->first();

        $invalidCredentials = $user === null
            || ! Hash::check($credentials['password'], $user->password)
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

        return response()->json([
            'token' => $token,
            'user' => (new CurrentUserResource($user))->resolve($request),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([], 204);
    }
}
