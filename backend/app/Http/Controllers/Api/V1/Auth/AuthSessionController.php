<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\CurrentUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if ($user === null || ! Auth::attempt([
            'email' => $user->email,
            'password' => $credentials['password'],
        ])) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are invalid.'],
            ]);
        }

        $request->session()->regenerate();

        return (new CurrentUserResource($request->user()))
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([], 204);
    }
}
