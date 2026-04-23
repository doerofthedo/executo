<?php

declare(strict_types=1);

use App\Models\District;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

function districtUserApiUser(string $email): User
{
    return User::query()->create([
        'name' => 'District',
        'surname' => 'User',
        'email' => $email,
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);
}

function districtUserApiDistrict(User $owner, int $number = 880): District
{
    return District::query()->create([
        'number' => $number,
        'bailiff_name' => 'Inese',
        'bailiff_surname' => 'Berzina',
        'court' => 'Rigas apgabaltiesa',
        'address' => 'Lacplesa iela 1, Riga',
        'disabled' => false,
        'owner_id' => $owner->id,
    ]);
}

function attachDistrictRole(District $district, User $user, string $roleName): void
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

test('district admin can list district users with roles permissions and owner flags', function (): void {
    $owner = districtUserApiUser('owner-users@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-users@executo.local');
    $districtMember = districtUserApiUser('district-member-users@executo.local');
    $district = districtUserApiDistrict($owner);

    $districtMember->forceFill([
        'email_verified_at' => null,
        'disabled' => true,
    ])->save();

    attachDistrictRole($district, $owner, 'district.admin');
    attachDistrictRole($district, $districtAdmin, 'district.admin');
    attachDistrictRole($district, $districtMember, 'district.user');

    Sanctum::actingAs($districtAdmin, ['*']);

    $response = $this->getJson(sprintf('/api/v1/districts/%s/users', $district->ulid));

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonFragment([
            'ulid' => $districtAdmin->ulid,
            'email' => $districtAdmin->email,
            'role' => 'district.admin',
            'is_owner' => false,
        ])
        ->assertJsonFragment([
            'ulid' => $owner->ulid,
            'email' => $owner->email,
            'role' => 'district.admin',
            'is_owner' => true,
        ])
        ->assertJsonFragment([
            'ulid' => $districtMember->ulid,
            'email' => $districtMember->email,
            'role' => 'district.user',
            'disabled' => true,
            'is_email_verified' => false,
        ])
        ->assertJsonMissingPath('data.0.id');

    expect($response->json('data.0.permissions'))->toBeArray()->not->toBeEmpty();
});

test('district users without admin membership cannot manage district users', function (): void {
    $owner = districtUserApiUser('owner-permissions@executo.local');
    $districtUser = districtUserApiUser('plain-district-user@executo.local');
    $member = districtUserApiUser('target-member@executo.local');
    $district = districtUserApiDistrict($owner, 881);

    attachDistrictRole($district, $districtUser, 'district.user');
    attachDistrictRole($district, $member, 'district.user');

    Sanctum::actingAs($districtUser, ['*']);

    $this->getJson(sprintf('/api/v1/districts/%s/users', $district->ulid))
        ->assertForbidden();

    $this->patchJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $member->ulid), [
        'role' => 'district.manager',
    ])->assertForbidden();

    $otherDistrict = districtUserApiDistrict($owner, 889);
    attachDistrictRole($otherDistrict, $member, 'district.user');

    $this->getJson(sprintf('/api/v1/districts/%s/users', $otherDistrict->ulid))
        ->assertNotFound();
});

test('district admin can update a district user role and keep a single membership row', function (): void {
    $owner = districtUserApiUser('owner-update@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-update@executo.local');
    $member = districtUserApiUser('member-update@executo.local');
    $district = districtUserApiDistrict($owner, 882);

    attachDistrictRole($district, $districtAdmin, 'district.admin');
    attachDistrictRole($district, $member, 'district.user');

    Sanctum::actingAs($districtAdmin, ['*']);

    $response = $this->patchJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $member->ulid), [
        'role' => 'district.manager',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.ulid', $member->ulid)
        ->assertJsonPath('data.role', 'district.manager');

    $roleId = Role::query()
        ->where('name', 'district.manager')
        ->where('guard_name', 'web')
        ->value('id');

    expect($roleId)->toBeInt();

    expect(DB::table('district_user')
        ->where('district_id', $district->id)
        ->where('user_id', $member->id)
        ->count())->toBe(1);

    $this->assertDatabaseHas('district_user', [
        'district_id' => $district->id,
        'user_id' => $member->id,
        'role_id' => $roleId,
    ]);
});

test('district user assignment rejects roles outside district scope', function (): void {
    $owner = districtUserApiUser('owner-invalid-role@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-invalid-role@executo.local');
    $member = districtUserApiUser('member-invalid-role@executo.local');
    $district = districtUserApiDistrict($owner, 887);

    attachDistrictRole($district, $districtAdmin, 'district.admin');

    Sanctum::actingAs($districtAdmin, ['*']);

    $this->postJson(sprintf('/api/v1/districts/%s/users', $district->ulid), [
        'email' => $member->email,
        'role' => 'app.admin',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

test('district user assignment rejects an existing membership instead of creating another role', function (): void {
    $owner = districtUserApiUser('owner-existing-membership@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-existing-membership@executo.local');
    $member = districtUserApiUser('member-existing-membership@executo.local');
    $district = districtUserApiDistrict($owner, 888);

    attachDistrictRole($district, $districtAdmin, 'district.admin');
    attachDistrictRole($district, $member, 'district.user');

    Sanctum::actingAs($districtAdmin, ['*']);

    $response = $this->postJson(sprintf('/api/v1/districts/%s/users', $district->ulid), [
        'email' => $member->email,
        'role' => 'district.manager',
    ]);

    $response
        ->assertStatus(422);

    expect(DB::table('district_user')
        ->where('district_id', $district->id)
        ->where('user_id', $member->id)
        ->count())->toBe(1);

    $userRoleId = Role::query()
        ->where('name', 'district.user')
        ->where('guard_name', 'web')
        ->value('id');

    $this->assertDatabaseHas('district_user', [
        'district_id' => $district->id,
        'user_id' => $member->id,
        'role_id' => $userRoleId,
    ]);
});

test('district admin can remove a district user', function (): void {
    $owner = districtUserApiUser('owner-delete@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-delete@executo.local');
    $member = districtUserApiUser('member-delete@executo.local');
    $district = districtUserApiDistrict($owner, 883);

    attachDistrictRole($district, $districtAdmin, 'district.admin');
    attachDistrictRole($district, $member, 'district.user');

    Sanctum::actingAs($districtAdmin, ['*']);

    $this->deleteJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $member->ulid))
        ->assertNoContent();

    $this->assertDatabaseMissing('district_user', [
        'district_id' => $district->id,
        'user_id' => $member->id,
    ]);
});

test('nested district user routes reject users from another district', function (): void {
    $owner = districtUserApiUser('owner-binding-users@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-binding-users@executo.local');
    $member = districtUserApiUser('member-binding-users@executo.local');
    $firstDistrict = districtUserApiDistrict($owner, 884);
    $secondDistrict = districtUserApiDistrict($owner, 885);

    attachDistrictRole($firstDistrict, $districtAdmin, 'district.admin');
    attachDistrictRole($firstDistrict, $member, 'district.user');
    attachDistrictRole($secondDistrict, $districtAdmin, 'district.admin');

    Sanctum::actingAs($districtAdmin, ['*']);

    $this->patchJson(sprintf('/api/v1/districts/%s/users/%s', $secondDistrict->ulid, $member->ulid), [
        'role' => 'district.manager',
    ])->assertNotFound();
});

test('district owner membership cannot be updated or removed', function (): void {
    $owner = districtUserApiUser('owner-protected@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-protected@executo.local');
    $district = districtUserApiDistrict($owner, 886);

    attachDistrictRole($district, $owner, 'district.admin');
    attachDistrictRole($district, $districtAdmin, 'district.admin');

    Sanctum::actingAs($districtAdmin, ['*']);

    $this->patchJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $owner->ulid), [
        'role' => 'district.user',
    ])->assertStatus(422);

    $this->deleteJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $owner->ulid))
        ->assertStatus(422);
});

test('district user membership create update and delete actions are written to the security audit log', function (): void {
    $owner = districtUserApiUser('owner-audit-district-users@executo.local');
    $districtAdmin = districtUserApiUser('district-admin-audit-district-users@executo.local');
    $member = districtUserApiUser('member-audit-district-users@executo.local');
    $district = districtUserApiDistrict($owner, 890);

    attachDistrictRole($district, $districtAdmin, 'district.admin');

    Sanctum::actingAs($districtAdmin, ['*']);

    $this->postJson(sprintf('/api/v1/districts/%s/users', $district->ulid), [
        'email' => $member->email,
        'role' => 'district.user',
    ])->assertCreated();

    $createActivity = Activity::query()->latest('id')->firstOrFail();
    $createProperties = $createActivity->properties->toArray();

    expect($createActivity->log_name)->toBe('security')
        ->and($createActivity->event)->toBe('api.v1.district-users.store.success')
        ->and($createProperties['route_name'])->toBe('api.v1.district-users.store')
        ->and($createProperties['district_ulid'])->toBe($district->ulid)
        ->and($createProperties['actor']['user_ulid'])->toBe($districtAdmin->ulid)
        ->and($createProperties['input']['email'])->toBe($member->email)
        ->and($createProperties['input']['role'])->toBe('district.user');

    $this->patchJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $member->ulid), [
        'role' => 'district.manager',
    ])->assertOk();

    $updateActivity = Activity::query()->latest('id')->firstOrFail();
    $updateProperties = $updateActivity->properties->toArray();

    expect($updateActivity->event)->toBe('api.v1.district-users.update.success')
        ->and($updateProperties['route_name'])->toBe('api.v1.district-users.update')
        ->and($updateProperties['district_ulid'])->toBe($district->ulid)
        ->and($updateProperties['subject_user_ulid'])->toBe($member->ulid)
        ->and($updateProperties['input']['role'])->toBe('district.manager');

    $this->deleteJson(sprintf('/api/v1/districts/%s/users/%s', $district->ulid, $member->ulid))
        ->assertNoContent();

    $deleteActivity = Activity::query()->latest('id')->firstOrFail();
    $deleteProperties = $deleteActivity->properties->toArray();

    expect($deleteActivity->event)->toBe('api.v1.district-users.destroy.success')
        ->and($deleteProperties['route_name'])->toBe('api.v1.district-users.destroy')
        ->and($deleteProperties['district_ulid'])->toBe($district->ulid)
        ->and($deleteProperties['subject_user_ulid'])->toBe($member->ulid)
        ->and($deleteProperties['status_code'])->toBe(204);
});
