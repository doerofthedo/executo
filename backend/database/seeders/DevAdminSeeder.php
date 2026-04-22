<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

final class DevAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@executo.local'],
            [
                'name' => 'System',
                'surname' => 'Administrator',
                'password' => Hash::make('123'),
                'disabled' => false,
                'email_verified_at' => Carbon::now(),
                'mfa_enabled' => false,
            ],
        );

        $admin->syncRoles(['app.admin']);
    }
}
