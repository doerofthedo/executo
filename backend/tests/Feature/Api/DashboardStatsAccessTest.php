<?php

declare(strict_types=1);

use App\Models\District;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

function dashboardStatsUser(string $email): User
{
    return User::query()->create([
        'name' => 'Dashboard',
        'surname' => 'User',
        'email' => $email,
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);
}

function dashboardStatsDistrict(User $owner, int $number = 169): District
{
    return District::query()->create([
        'number' => $number,
        'bailiff_name' => 'Krisjanis',
        'bailiff_surname' => 'Feldmans',
        'court' => 'Rigas apgabaltiesa',
        'address' => 'Brivibas iela 1, Riga',
        'disabled' => false,
        'owner_id' => $owner->id,
    ]);
}

function attachDashboardDistrictRole(District $district, User $user, string $roleName): void
{
    $roleId = Role::query()
        ->where('name', $roleName)
        ->where('guard_name', 'web')
        ->value('id');

    if (! is_int($roleId)) {
        throw new RuntimeException(sprintf('Role [%s] was not seeded.', $roleName));
    }

    $district->users()->attach($user->id, ['role_id' => $roleId]);
}

test('district user can load dashboard stats for their district membership', function (): void {
    $owner = dashboardStatsUser('owner-dashboard@executo.local');
    $districtUser = dashboardStatsUser('district-user-dashboard@executo.local');
    $district = dashboardStatsDistrict($owner);

    attachDashboardDistrictRole($district, $districtUser, 'district.user');

    Sanctum::actingAs($districtUser, ['*']);

    $response = $this->getJson('/api/v1/districts/stats');

    $response
        ->assertOk()
        ->assertJsonPath('districts_count', 1)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.district.ulid', $district->ulid)
        ->assertJsonPath('data.0.can_create_debtor', false)
        ->assertJsonPath('data.0.can_create_debt', false)
        ->assertJsonPath('data.0.can_create_payment', false);
});

test('district admin sees dashboard create permissions for their district membership', function (): void {
    $owner = dashboardStatsUser('owner-dashboard-admin@executo.local');
    $districtAdmin = dashboardStatsUser('district-admin-dashboard@executo.local');
    $district = dashboardStatsDistrict($owner, 70);

    attachDashboardDistrictRole($district, $districtAdmin, 'district.admin');

    Sanctum::actingAs($districtAdmin, ['*']);

    $response = $this->getJson('/api/v1/districts/stats');

    $response
        ->assertOk()
        ->assertJsonPath('districts_count', 1)
        ->assertJsonPath('data.0.district.ulid', $district->ulid)
        ->assertJsonPath('data.0.can_create_debtor', true)
        ->assertJsonPath('data.0.can_create_debt', true)
        ->assertJsonPath('data.0.can_create_payment', true)
        ->assertJsonPath('data.0.can_manage_users', true);
});
