<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@executo.local'],
            [
                'name' => 'System',
                'surname' => 'Administrator',
                'password' => '$2y$12$yGE3TDYt6XBji2zaqjUfVenc3KdjUs7lEjoW2IXOk8w7D6wHc.Omi',
                'mfa_enabled' => false,
            ],
        );

        $admin->syncRoles(['app.admin']);
    }
}
