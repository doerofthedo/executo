<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\District;
use App\Models\User;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.customer.view')
            && $this->canAccessCurrentDistrict($user);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('district.customer.view')
            && $this->canAccessDistrict($user, $customer->district_id);
    }

    public function create(User $user): bool
    {
        return $user->can('district.customer.create')
            && $this->canAccessCurrentDistrict($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('district.customer.update')
            && $this->canAccessDistrict($user, $customer->district_id);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('district.customer.delete')
            && $this->canAccessDistrict($user, $customer->district_id);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->can('district.customer.update')
            && $this->canAccessDistrict($user, $customer->district_id);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can('district.customer.delete')
            && $this->canAccessDistrict($user, $customer->district_id);
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
