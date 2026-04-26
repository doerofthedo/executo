<?php

declare(strict_types=1);

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserProfileUpdated;
use App\Models\User;
use App\Notifications\User\NameChangedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

final class NotifyAdminsOnProfileChange
{
    public function handle(UserProfileUpdated $event): void
    {
        $changedUser = $event->user;

        // App admins — look up role by name first so we never throw if it doesn't exist
        // (Spatie's role() scope throws RoleDoesNotExist; querying by ID is guard-agnostic)
        $appAdminRole = Role::where('name', 'app.admin')->first();
        $appAdmins = $appAdminRole !== null
            ? User::whereHas('roles', static fn ($q) => $q->where('roles.id', $appAdminRole->id))->get()
            : collect();

        // District admins in every district the changed user belongs to
        $districtIds = $changedUser->districts()->pluck('districts.id');

        $districtAdminUserIds = collect();

        if ($districtIds->isNotEmpty()) {
            $districtAdminRole = Role::where('name', 'district.admin')->first();

            if ($districtAdminRole !== null) {
                $districtAdminUserIds = \DB::table('district_user')
                    ->whereIn('district_id', $districtIds)
                    ->where('role_id', $districtAdminRole->id)
                    ->pluck('user_id');
            }
        }

        $districtAdmins = $districtAdminUserIds->isNotEmpty()
            ? User::whereIn('id', $districtAdminUserIds)->get()
            : collect();

        // Merge, deduplicate, exclude the changed user themselves
        $recipients = $appAdmins
            ->merge($districtAdmins)
            ->unique('id')
            ->reject(static fn (User $u): bool => $u->id === $changedUser->id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NameChangedNotification($changedUser, $event->oldName, $event->newName));
    }
}
