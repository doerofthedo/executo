<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.customer.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('district.customer.view');
    }
}
