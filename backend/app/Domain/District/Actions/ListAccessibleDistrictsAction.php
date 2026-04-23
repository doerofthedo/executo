<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListAccessibleDistrictsAction
{
    /**
     * @return Collection<int, District>
     */
    public function execute(User $user): Collection
    {
        $query = District::query()
            ->orderBy('number');

        if (! $user->hasRole('app.admin')) {
            $query->where(function ($builder) use ($user): void {
                $builder
                    ->where('owner_id', $user->id)
                    ->orWhereHas('users', static fn ($usersQuery) => $usersQuery->where('users.id', $user->id));
            });
        }

        return $query->get();
    }
}
