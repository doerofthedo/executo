<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function view(User $currentUser, User $user): bool
    {
        return $currentUser->is($user) || $currentUser->can('app.user.view');
    }

    public function update(User $currentUser, User $user): bool
    {
        return $currentUser->is($user) || $currentUser->can('app.user.manage');
    }
}
