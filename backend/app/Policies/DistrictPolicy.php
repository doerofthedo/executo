<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\District;
use App\Models\User;

final class DistrictPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.district.view');
    }

    public function view(User $user, District $district): bool
    {
        return $user->can('district.district.view');
    }

    public function create(User $user): bool
    {
        return $user->can('district.district.manage');
    }

    public function update(User $user, District $district): bool
    {
        return $user->can('district.district.manage');
    }
}
