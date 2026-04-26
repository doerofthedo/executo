<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\District\Services\DistrictPermissionResolver;
use App\Models\Customer;
use App\Models\District;
use App\Models\User;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.view', $districtId);
    }

    public function view(User $user, Customer $customer): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.view', $customer->district_id);
    }

    public function create(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.create', $districtId);
    }

    public function update(User $user, Customer $customer): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.update', $customer->district_id);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.delete', $customer->district_id);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.update', $customer->district_id);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.customer.delete', $customer->district_id);
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
