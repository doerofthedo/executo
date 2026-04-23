<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\Actions\UpdateUserProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Auth\UserProfileResource;
use App\Models\User;

final class UserController extends Controller
{
    public function __construct(
        private readonly UpdateUserProfileAction $updateUserProfile,
    ) {
    }

    public function show(User $user): UserProfileResource
    {
        $this->authorize('view', $user);

        return new UserProfileResource($user->load('preference'));
    }

    public function update(UpdateUserRequest $request, User $user): UserProfileResource
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if ($data === []) {
            abort(422, 'No changes submitted.');
        }

        $currentUser = $request->user();
        if ($currentUser === null) {
            abort(403);
        }

        return new UserProfileResource($this->updateUserProfile->execute($user, $currentUser, $data)->load('preference'));
    }
}
