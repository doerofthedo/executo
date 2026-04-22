<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\District\StoreDistrictUserRequest;
use App\Http\Resources\DistrictUserResource;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Models\Role;

final class DistrictUserController extends Controller
{
    public function store(StoreDistrictUserRequest $request, District $district): JsonResponse
    {
        $this->authorize('update', $district);

        $validated = $request->validated();
        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null) {
            abort(422, 'No registered user with this email address was found.');
        }

        $role = Role::query()
            ->where('name', $validated['role'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        $existingMembership = $district->users()
            ->where('users.id', $user->id)
            ->exists();

        if ($existingMembership) {
            $district->users()->updateExistingPivot($user->id, ['role_id' => $role->id]);
        } else {
            $district->users()->attach($user->id, ['role_id' => $role->id]);
        }

        $districtUser = $district->users()
            ->where('users.id', $user->id)
            ->firstOrFail();

        $districtUser->setAttribute('district_role', $role->name);

        return (new DistrictUserResource($districtUser))
            ->response()
            ->setStatusCode($existingMembership ? Response::HTTP_OK : Response::HTTP_CREATED);
    }
}
