<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\Auth\UserProfileResource;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UserController extends Controller
{
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
        $isElevated = $currentUser !== null && ! $currentUser->is($user)
            ? true
            : ($currentUser?->can('app.user.manage') ?? false);

        if (! $isElevated) {
            unset($data['email'], $data['disabled']);
        }

        if ($data === []) {
            abort(422, 'No changes submitted.');
        }

        DB::transaction(function () use ($data, $user): void {
            $userPayload = array_intersect_key($data, array_flip([
                'name',
                'surname',
                'email',
                'password',
                'disabled',
            ]));

            if ($userPayload !== []) {
                $user->fill($userPayload);

                if ($user->isDirty()) {
                    $user->save();

                    if (array_key_exists('password', $userPayload)) {
                        $user->tokens()->delete();
                    }
                }
            }

            $preferencePayload = array_intersect_key($data, array_flip([
                'locale',
                'date_format',
                'decimal_separator',
                'thousand_separator',
                'table_page_size',
            ]));

            if ($preferencePayload !== []) {
                UserPreference::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    $preferencePayload,
                );
            }
        });

        $freshUser = $user->fresh();

        if ($freshUser === null) {
            throw new NotFoundHttpException();
        }

        return new UserProfileResource($freshUser->load('preference'));
    }
}
