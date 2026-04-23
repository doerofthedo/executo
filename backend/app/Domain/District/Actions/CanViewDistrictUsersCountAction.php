<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use App\Models\User;
use Spatie\Permission\Models\Role;

final readonly class CanViewDistrictUsersCountAction
{
    public function execute(User $user, District $district): bool
    {
        if ($user->hasRole('app.admin') || $district->owner_id === $user->id) {
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
