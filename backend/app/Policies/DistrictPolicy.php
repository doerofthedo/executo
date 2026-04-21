<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\District;
use App\Models\User;

final class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.district.view')
            && $user->districts()->exists();
    }

    public function view(User $user, District $district): bool
    {
        return $user->can('district.district.view')
            && $this->canAccessDistrict($user, $district->id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, District $district): bool
    {
        return $user->can('district.district.manage')
            && $this->canAccessDistrict($user, $district->id);
    }

    private function canAccessDistrict(User $user, int $districtId): bool
    {
        return $user->districts()->where('districts.id', $districtId)->exists()
            || District::query()->where('id', $districtId)->where('owner_id', $user->id)->exists();
    }
}
