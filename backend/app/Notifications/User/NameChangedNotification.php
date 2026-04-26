<?php

declare(strict_types=1);

namespace App\Notifications\User;

use App\Models\User;
use Illuminate\Notifications\Notification;

final class NameChangedNotification extends Notification
{
    public function __construct(
        private readonly User $changedUser,
        private readonly string $oldName,
        private readonly string $newName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user.name_changed',
            'user_ulid' => $this->changedUser->ulid,
            'old_name' => $this->oldName,
            'new_name' => $this->newName,
            'changed_at' => now()->toAtomString(),
        ];
    }
}
