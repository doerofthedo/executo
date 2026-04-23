<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Auth\Actions\AuthenticateUserAction;
use App\Domain\Auth\Actions\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthSessionResource;
use App\Http\Resources\EmptyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class AuthSessionController extends Controller
{
    public function __construct(
        private readonly AuthenticateUserAction $authenticateUser,
        private readonly LogoutUserAction $logoutUser,
    ) {
    }

    public function store(LoginRequest $request): JsonResponse
    {
        Gate::authorize('login');
        /** @var array{login: string, password: string} $credentials */
        $credentials = $request->validated();
        $session = $this->authenticateUser->execute($credentials, $request);

        return (new AuthSessionResource($session->user, $session->token))->response();
    }

    public function destroy(Request $request): JsonResponse
    {
        Gate::authorize('logout');
        $this->logoutUser->execute($request);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
