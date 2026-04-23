<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

final readonly class UpdateDistrictUserAction
{
    /**
     * @param  array{role: string}  $payload
     */
    public function execute(District $district, User $user, array $payload): User
    {
        if ($district->owner_id === $user->id) {
            abort(422, 'The district owner role cannot be changed.');
        }

        $membershipExists = $district->users()
            ->where('users.id', $user->id)
            ->exists();

        if (! $membershipExists) {
            abort(404);
        }

        $role = Role::query()
            ->where('name', $payload['role'])
            ->where('guard_name', 'web')
            ->firstOrFail();

        DB::transaction(function () use ($district, $user, $role): void {
            $district->users()->detach($user->id);
            $district->users()->attach($user->id, ['role_id' => $role->id]);
        });

        $districtUser = $district->users()
            ->where('users.id', $user->id)
            ->firstOrFail();

        $districtUser->setAttribute('district_role', $role->name);
        $districtUser->setAttribute('district_permissions', $role->permissions()->pluck('name')->sort()->values()->all());
        $districtUser->setAttribute('district_is_owner', false);

        return $districtUser;
    }
}
