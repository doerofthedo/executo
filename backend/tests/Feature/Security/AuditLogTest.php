<?php

declare(strict_types=1);

use App\Models\District;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

test('failed login attempt is written to the security audit log', function (): void {
    $user = User::query()->create([
        'name' => 'Audit',
        'surname' => 'User',
        'email' => 'audit-login@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);

    $activity = Activity::query()->latest('id')->firstOrFail();
    $properties = $activity->properties->toArray();

    expect($activity->log_name)->toBe('security')
        ->and($activity->event)->toBe('api.v1.auth.login.failure')
        ->and($properties['route_name'])->toBe('api.v1.auth.login')
        ->and($properties['status_code'])->toBe(422)
        ->and($properties['outcome'])->toBe('failure')
        ->and($properties['input']['login'])->toBe($user->email)
        ->and(isset($properties['input']['password']))->toBeFalse();
});

test('customer creation writes a successful security audit entry', function (): void {
    $owner = User::query()->create([
        'name' => 'District',
        'surname' => 'Owner',
        'email' => 'district-owner@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    $admin = User::query()->create([
        'name' => 'App',
        'surname' => 'Admin',
        'email' => 'app-admin@executo.local',
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    $admin->assignRole('app.admin');

    $district = District::query()->create([
        'number' => 601,
        'bailiff_name' => 'Anna',
        'bailiff_surname' => 'Kalnina',
        'court' => 'Rigas pilsetas tiesa',
        'address' => 'Brivibas iela 1, Riga',
        'disabled' => false,
        'owner_id' => $owner->id,
    ]);

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson(sprintf('/api/v1/districts/%s/customers', $district->ulid), [
        'type' => 'physical',
        'first_name' => 'Ilze',
        'last_name' => 'Ozolina',
        'email' => 'ilze.ozolina@example.com',
    ]);

    $response->assertCreated();

    $activity = Activity::query()->latest('id')->firstOrFail();
    $properties = $activity->properties->toArray();

    expect($activity->log_name)->toBe('security')
        ->and($activity->event)->toBe('api.v1.customers.store.success')
        ->and($properties['route_name'])->toBe('api.v1.customers.store')
        ->and($properties['status_code'])->toBe(201)
        ->and($properties['outcome'])->toBe('success')
        ->and($properties['district_ulid'])->toBe($district->ulid)
        ->and($properties['actor']['user_ulid'])->toBe($admin->ulid)
        ->and($properties['input']['first_name'])->toBe('Ilze');
});
