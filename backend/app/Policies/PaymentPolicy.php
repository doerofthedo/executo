<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\District\Services\DistrictPermissionResolver;
use App\Models\District;
use App\Models\Payment;
use App\Models\User;

final class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.payment.view', $districtId);
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($payment->debt === null) {
            return false;
        }

        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.payment.view', $payment->debt->district_id);
    }

    public function create(User $user): bool
    {
        $districtId = $this->currentDistrictId();

        return is_int($districtId)
            && app(DistrictPermissionResolver::class)->hasPermission($user, 'district.payment.create', $districtId);
    }

    public function update(User $user, Payment $payment): bool
    {
        if ($payment->debt === null) {
            return false;
        }

        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.payment.update', $payment->debt->district_id);
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($payment->debt === null) {
            return false;
        }

        return app(DistrictPermissionResolver::class)->hasPermission($user, 'district.payment.delete', $payment->debt->district_id);
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
