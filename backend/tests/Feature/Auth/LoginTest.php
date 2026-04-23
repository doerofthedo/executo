<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('unverified user can log in and receives auth session data', function (): void {
    $password = '$oTJ4%ahsLZN9xJm';
    $user = User::query()->create([
        'name' => 'Krisjanis',
        'surname' => 'Feldmans',
        'email' => 'unverified-login-test@executo.local',
        'password' => Hash::make($password),
        'disabled' => false,
        'email_verified_at' => null,
        'mfa_enabled' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => $password,
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.is_email_verified', false)
        ->assertJsonStructure([
            'token',
            'user' => [
                'ulid',
                'email',
                'name',
                'surname',
                'is_email_verified',
            ],
        ]);
});
