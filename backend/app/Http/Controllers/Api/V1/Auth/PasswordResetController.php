<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
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

        $status = Password::broker()->reset(
            $validated,
            static function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
