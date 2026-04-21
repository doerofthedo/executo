<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Debt;
use App\Models\District;
use App\Models\User;

final class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.debt.view')
            && $this->canAccessCurrentDistrict($user);
    }

    public function view(User $user, Debt $debt): bool
    {
        return $user->can('district.debt.view')
            && $this->canAccessDistrict($user, $debt->district_id);
    }

    public function create(User $user): bool
    {
        return $user->can('district.debt.create')
            && $this->canAccessCurrentDistrict($user);
    }

    public function update(User $user, Debt $debt): bool
    {
        return $user->can('district.debt.update')
            && $this->canAccessDistrict($user, $debt->district_id);
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $user->can('district.debt.delete')
            && $this->canAccessDistrict($user, $debt->district_id);
    }

    private function canAccessCurrentDistrict(User $user): bool
    {
        $districtId = request()->attributes->get('current_district_id');

        if (! is_int($districtId)) {
            $district = request()->route('district');

            if ($district instanceof District) {
                $districtId = $district->id;
            }
        }

        return $districtId === null || $this->canAccessDistrict($user, $districtId);
    }

    private function canAccessDistrict(User $user, int $districtId): bool
    {
        return $user->districts()->where('districts.id', $districtId)->exists()
            || District::query()->where('id', $districtId)->where('owner_id', $user->id)->exists();
    }
}
