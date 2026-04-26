<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\District\Services\DistrictPermissionResolver;
use App\Models\Debtor;
use App\Models\District;
use App\Models\User;

final class DebtorPolicy
{
    public function viewAny(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.view', $districtId);
    }

    public function view(User $user, Debtor $debtor): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.view', $debtor->district_id);
    }

    public function create(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.create', $districtId);
    }

    public function update(User $user, Debtor $debtor): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.update', $debtor->district_id);
    }

    public function delete(User $user, Debtor $debtor): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.delete', $debtor->district_id);
    }

    public function restore(User $user, Debtor $debtor): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.update', $debtor->district_id);
    }

    public function forceDelete(User $user, Debtor $debtor): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.debtor.delete', $debtor->district_id);
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
