<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;

final class DebtPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('district.debt.view');
    }

    public function view(User $user, Debt $debt): bool
    {
        return $user->can('district.debt.view');
    }

    public function create(User $user): bool
    {
        return $user->can('district.debt.create');
    }

    public function update(User $user, Debt $debt): bool
    {
        return $user->can('district.debt.update');
    }

    public function delete(User $user, Debt $debt): bool
    {
        return $user->can('district.debt.delete');
    }
}
