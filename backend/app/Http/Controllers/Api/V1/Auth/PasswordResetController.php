<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RequestPasswordResetAction;
use App\Domain\Auth\Actions\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\EmptyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class PasswordResetController extends Controller
{
    public function __construct(
        private readonly RequestPasswordResetAction $requestPasswordReset,
        private readonly ResetPasswordAction $resetPassword,
    ) {
    }

    public function store(ForgotPasswordRequest $request): Response
    {
        Gate::authorize('requestPasswordReset');
        $this->requestPasswordReset->execute($request->validated()['email']);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function update(ResetPasswordRequest $request): JsonResponse
    {
        Gate::authorize('resetPassword');
        $validated = $request->validated();
        $this->resetPassword->execute($validated['token'], $validated['password']);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
