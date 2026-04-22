<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class PasswordResetController extends Controller
{
    public function store(ForgotPasswordRequest $request): Response
    {
        $user = User::query()
            ->where('email', $request->validated()['email'])
            ->first();

        if ($user !== null && ! $user->disabled) {
            Password::broker()->sendResetLink([
                'email' => $user->email,
            ]);
        }

        return response()->noContent(Response::HTTP_ACCEPTED);
    }

    public function update(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $resetToken = DB::table('password_reset_tokens')
            ->whereNotNull('created_at')
            ->orderByDesc('created_at')
            ->get(['email', 'token'])
            ->first(static fn (object $tokenRow): bool => Hash::check($validated['token'], (string) $tokenRow->token));

        if ($resetToken === null) {
            throw ValidationException::withMessages([
                'token' => [trans(Password::INVALID_TOKEN)],
            ]);
        }

        $user = User::query()
            ->where('email', $resetToken->email)
            ->first();

        if ($user === null || Password::broker()->tokenExists($user, $validated['token']) === false) {
            throw ValidationException::withMessages([
                'token' => [trans(Password::INVALID_TOKEN)],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $user->tokens()->delete();

        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
