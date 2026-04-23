<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use App\Models\User;
use Spatie\Permission\Models\Role;

final readonly class StoreDistrictUserAction
{
    /**
     * @param  array{email: string, role: string}  $payload
     * @return array{user: User, created: bool}
     */
    public function execute(District $district, array $payload): array
    {
        $user = User::query()
            ->where('email', $payload['email'])
            ->first();

        if ($user === null) {
            abort(422, 'No registered user with this email address was found.');
        }

        $role = Role::query()
            ->where('name', $payload['role'])
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

        return [
            'user' => $districtUser,
            'created' => ! $existingMembership,
        ];
    }
}
