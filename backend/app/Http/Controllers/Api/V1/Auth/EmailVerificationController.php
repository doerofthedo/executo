<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RequestEmailVerificationAction;
use App\Domain\Auth\Actions\VerifyEmailAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailTokenRequest;
use App\Http\Resources\Auth\EmailVerificationStatusResource;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

    public function verifyToken(VerifyEmailTokenRequest $request): JsonResponse
    {
        [$user, $hash] = $this->resolveSignedVerification($request->string('token')->toString());

        Gate::authorize('verifyEmail', $user);

        if (! hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            abort(403);
        }

        $this->verifyEmail->execute($user);

        return (new EmailVerificationStatusResource(verified: true))->response();
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function resolveSignedVerification(string $token): array
    {
        try {
            $signedUrl = Crypt::decryptString($token);
        } catch (\Throwable) {
            throw new AccessDeniedHttpException();
        }

        $signedRequest = Request::create($signedUrl, 'GET');

        if (! URL::hasValidSignature($signedRequest)) {
            throw new AccessDeniedHttpException();
        }

        $path = (string) parse_url($signedUrl, PHP_URL_PATH);

        if (preg_match('#/api/v1/auth/email/verify/([^/]+)/([^/?]+)$#', $path, $matches) !== 1) {
            throw new AccessDeniedHttpException();
        }

        $user = User::query()
            ->where('ulid', $matches[1])
            ->firstOrFail();

        return [$user, $matches[2]];
    }
}
