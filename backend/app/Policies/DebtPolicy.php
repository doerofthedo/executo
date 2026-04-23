<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\District\Services\DistrictPermissionResolver;
use App\Models\Debt;
use App\Models\District;
use App\Models\User;

final class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debt.view', $districtId);
    }

    public function view(User $user, Debt $debt): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debt.view', $debt->district_id);
    }

    public function create(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debt.create', $districtId);
    }

    public function update(User $user, Debt $debt): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debt.update', $debt->district_id);
    }

    public function delete(User $user, Debt $debt): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debt.delete', $debt->district_id);
    }

    private function currentDistrictId(): ?int
    {
        $districtId = request()->attributes->get('current_district_id');

        if (! is_int($districtId)) {
            $district = request()->route('district');

            if ($district instanceof District) {
                $districtId = $district->id;
            }
        }

        return is_int($districtId) ? $districtId : null;
    }
}
