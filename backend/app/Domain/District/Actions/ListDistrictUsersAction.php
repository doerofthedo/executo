<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

final readonly class ListDistrictUsersAction
{
    /**
     * @return Collection<int, User>
     */
    public function execute(District $district): Collection
    {
        /** @var Collection<int, User> $users */
        $users = $district->users()
            ->select('users.*', 'district_user.role_id as district_role_id', 'roles.name as district_role')
            ->join('roles', 'roles.id', '=', 'district_user.role_id')
            ->orderBy('users.name')
            ->orderBy('users.surname')
            ->orderBy('users.email')
            ->get();

        /** @var array<int, array<int, string>> $permissionsByRoleId */
        $permissionsByRoleId = Role::query()
            ->whereIn('id', $users->pluck('district_role_id')->filter()->unique()->all())
            ->with('permissions:name')
            ->get()
            ->mapWithKeys(static fn (Role $role): array => [
                $role->id => $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->values()
                    ->all(),
            ])
            ->all();

        return $users->each(
            static function (User $user) use ($district, $permissionsByRoleId): void {
                $roleId = $user->getAttribute('district_role_id');

                $user->setAttribute(
                    'district_permissions',
                    is_int($roleId) ? ($permissionsByRoleId[$roleId] ?? []) : [],
                );
                $user->setAttribute('district_is_owner', $district->owner_id === $user->id);
            },
        );
    }
}
