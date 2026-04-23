<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\RequestEmailVerificationAction;
use App\Domain\Auth\Actions\RegisterUserAction;
use App\Domain\Auth\DTOs\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\RequestEmailVerificationRequest;
use App\Http\Resources\EmptyResource;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUser,
        private readonly RequestEmailVerificationAction $requestEmailVerification,
    ) {
    }

    public function store(RegisterRequest $request): Response
    {
        Gate::authorize('register');
        $validated = $request->validated();

        $this->registerUser->registerIfNew(new RegisterUserData(
            name: $validated['name'],
            surname: $validated['surname'],
            email: $validated['email'],
            password: $validated['password'],
            locale: $validated['locale'] ?? 'lv',
        ));

        return $this->accepted();
    }

    public function requestVerification(RequestEmailVerificationRequest $request): Response
    {
        Gate::authorize('requestEmailVerification');
        $this->requestEmailVerification->execute($request->validated()['email']);

        return $this->accepted();
    }

    private function accepted(): Response
    {
        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
