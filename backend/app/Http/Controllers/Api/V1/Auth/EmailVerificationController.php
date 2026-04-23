<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RequestEmailVerificationAction;
use App\Domain\Auth\Actions\VerifyEmailAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\EmailVerificationStatusResource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly RequestEmailVerificationAction $requestEmailVerification,
        private readonly VerifyEmailAction $verifyEmail,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('requestEmailVerification');
        $user = $request->user();

        if ($user === null) {
            throw new AuthorizationException();
        }

        if (! $user->hasVerifiedEmail()) {
            $this->requestEmailVerification->execute($user->email);
        }

        return (new EmailVerificationStatusResource(
            verified: $user->hasVerifiedEmail(),
            sent: ! $user->hasVerifiedEmail(),
        ))->response();
    }

    public function update(User $user, string $hash): JsonResponse
    {
        Gate::authorize('verifyEmail', $user);

        if (! hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            abort(403);
        }

        $this->verifyEmail->execute($user);

        return (new EmailVerificationStatusResource(verified: true))->response();
    }
}
