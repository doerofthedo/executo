<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserPreference;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

test('newly registered users receive europe riga timezone preference by default', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Timezone',
        'surname' => 'Default',
        'email' => 'timezone-default@executo.local',
        'password' => 'Secret123abc',
        'password_confirmation' => 'Secret123abc',
        'locale' => 'lv',
    ]);

    $response->assertAccepted();

    $user = User::query()->where('email', 'timezone-default@executo.local')->firstOrFail();

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'timezone' => 'Europe/Riga',
    ]);
});

test('user preferences endpoint returns and updates timezone', function (): void {
    $user = User::query()->create([
        'name' => 'Timezone',
        'surname' => 'Tester',
        'email' => 'timezone-tester@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    UserPreference::query()->create([
        'user_id' => $user->id,
        'locale' => 'lv',
        'timezone' => 'Europe/Riga',
        'date_format' => 'DD.MM.YYYY.',
        'decimal_separator' => ',',
        'thousand_separator' => ' ',
        'table_page_size' => 25,
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson(sprintf('/api/v1/users/%s', $user->ulid))
        ->assertOk()
        ->assertJsonPath('data.preferences.timezone', 'Europe/Riga');

    $this->patchJson(sprintf('/api/v1/users/%s', $user->ulid), [
        'timezone' => 'UTC',
    ])->assertOk()
        ->assertJsonPath('data.preferences.timezone', 'UTC');

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'timezone' => 'UTC',
    ]);
});

test('user preference timezone validation rejects unsupported values', function (): void {
    $user = User::query()->create([
        'name' => 'Timezone',
        'surname' => 'Invalid',
        'email' => 'timezone-invalid@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->patchJson(sprintf('/api/v1/users/%s', $user->ulid), [
        'timezone' => 'America/New_York',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['timezone']);
});
