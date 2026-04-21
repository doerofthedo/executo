<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RegisterUserAction;
use App\Domain\Auth\DTOs\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RequestEmailVerificationRequest;
use App\Models\User;
use Illuminate\Http\Response;

final class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUser,
    ) {
    }

    public function store(RegisterRequest $request): Response
    {
        $validated = $request->validated();

        $existingUser = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($existingUser !== null) {
            return response()->noContent(Response::HTTP_ACCEPTED);
        }

        $user = $this->registerUser->execute(new RegisterUserData(
            name: $validated['name'],
            surname: $validated['surname'],
            email: $validated['email'],
            password: $validated['password'],
            locale: $validated['locale'] ?? 'lv',
        ));

        $user->sendEmailVerificationNotification();

        return response()->noContent(Response::HTTP_ACCEPTED);
    }

    public function requestVerification(RequestEmailVerificationRequest $request): Response
    {
        $user = User::query()
            ->where('email', $request->validated()['email'])
            ->first();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->noContent(Response::HTTP_ACCEPTED);
    }
}
