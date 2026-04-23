<?php

declare(strict_types=1);

namespace App\Domain\District\Actions;

use App\Models\District;
use App\Models\User;

final readonly class DeleteDistrictUserAction
{
    public function execute(District $district, User $user): void
    {
        if ($district->owner_id === $user->id) {
            abort(422, 'The district owner cannot be removed from the district.');
        }

        $deletedCount = $district->users()->detach($user->id);

        if ($deletedCount === 0) {
            abort(404);
        }
    }
}
