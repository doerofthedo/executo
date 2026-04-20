<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmailVerificationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthorizationException();
        }

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'sent' => ! $user->hasVerifiedEmail(),
            'verified' => $user->hasVerifiedEmail(),
        ]);
    }

    public function update(User $user, string $hash): JsonResponse
    {
        if (! hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return response()->json([
            'verified' => true,
        ]);
    }
}
