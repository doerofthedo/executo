<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\District;
use App\Models\Payment;
use App\Models\User;

final class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.payment.view')
            && $this->canAccessCurrentDistrict($user);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($payment->debt === null) {
            return false;
        }

        return $user->can('district.payment.view')
            && $this->canAccessDistrict($user, $payment->debt->district_id);
    }

    public function create(User $user): bool
    {
        return $user->can('district.payment.create')
            && $this->canAccessCurrentDistrict($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($payment->debt === null) {
            return false;
        }

        return $user->can('district.payment.update')
            && $this->canAccessDistrict($user, $payment->debt->district_id);
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($payment->debt === null) {
            return false;
        }

        return $user->can('district.payment.delete')
            && $this->canAccessDistrict($user, $payment->debt->district_id);
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
