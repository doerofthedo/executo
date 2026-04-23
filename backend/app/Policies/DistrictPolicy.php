<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\District\Services\DistrictPermissionResolver;
use App\Models\District;
use App\Models\User;
use Spatie\Permission\Models\Role;

final class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return app(DistrictPermissionResolver::class)->hasAnyDistrictAccess($user);
    }

    public function view(User $user, District $district): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.district.view', $district->id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, District $district): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.district.manage', $district->id);
    }

    public function viewUsers(User $user, District $district): bool
    {
        return $this->canManageDistrictUsers($user, $district);
    }

    public function manageUsers(User $user, District $district): bool
    {
        return $this->canManageDistrictUsers($user, $district);
    }
    private function canManageDistrictUsers(User $user, District $district): bool
    {
        if ($district->owner_id === $user->id) {
            return true;
        }

        $districtAdminRoleId = Role::query()
            ->where('name', 'district.admin')
            ->where('guard_name', 'web')
            ->value('id');

        if (! is_int($districtAdminRoleId)) {
            return false;
        }

        return $district->users()
            ->where('users.id', $user->id)
            ->wherePivot('role_id', $districtAdminRoleId)
            ->exists();
    }
}
