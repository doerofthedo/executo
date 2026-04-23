<?php

declare(strict_types=1);

namespace App\Domain\District\Services;

use App\Models\District;
use App\Models\User;
use Spatie\Permission\Models\Role;

final readonly class DistrictPermissionResolver
{
    public function hasAnyDistrictAccess(User $user): bool
    {
        if ($user->hasRole('app.admin')) {
            return true;
        }

        return District::query()->where('owner_id', $user->id)->exists()
            || $user->districts()->exists();
    }

    public function canAccessDistrict(User $user, int $districtId): bool
    {
        if ($user->hasRole('app.admin')) {
            return true;
        }

        return District::query()->where('id', $districtId)->where('owner_id', $user->id)->exists()
            || $user->districts()->where('districts.id', $districtId)->exists();
    }

    public function hasPermission(User $user, string $permission, int $districtId): bool
    {
        if ($user->hasRole('app.admin')) {
            return true;
        }

        if (District::query()->where('id', $districtId)->where('owner_id', $user->id)->exists()) {
            return true;
        }

        $roleId = $user->districts()
            ->where('districts.id', $districtId)
            ->first()?->pivot?->role_id;

        if (! is_int($roleId)) {
            return false;
        }

        return Role::query()
            ->whereKey($roleId)
            ->whereHas('permissions', static fn ($query) => $query->where('name', $permission))
            ->exists();
    }
}
