<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RbacSeeder::class);
});

function apiUser(string $email, bool $admin = false): User
{
    $user = User::query()->create([
        'name' => 'Api',
        'surname' => 'Tester',
        'email' => $email,
        'password' => Hash::make('secret123'),
        'disabled' => false,
        'email_verified_at' => now(),
        'mfa_enabled' => false,
    ]);

    if ($admin) {
        $user->assignRole('app.admin');
    }

    return $user;
}

function apiDistrict(User $owner, int $number = 777): District
{
    return District::query()->create([
        'number' => $number,
        'bailiff_name' => 'Anna',
        'bailiff_surname' => 'Kalnina',
        'court' => 'Rigas pilsetas tiesa',
        'address' => 'Brivibas iela 1, Riga',
        'disabled' => false,
        'owner_id' => $owner->id,
    ]);
}

test('authenticated customer creation returns a resource with public identifiers only', function (): void {
    $owner = apiUser('customer-owner@executo.local');
    $admin = apiUser('customer-admin@executo.local', true);
    $district = apiDistrict($owner);

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson(sprintf('/api/v1/districts/%s/customers', $district->ulid), [
        'type' => 'physical',
        'first_name' => 'Ilze',
        'last_name' => 'Ozolina',
        'email' => 'ilze.ozolina@example.com',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.district_ulid', $district->ulid)
        ->assertJsonPath('data.type', 'physical')
        ->assertJsonMissingPath('data.id');

    expect(Customer::query()->where('email', 'ilze.ozolina@example.com')->exists())->toBeTrue();
});

test('customer creation validation failures return 422', function (): void {
    $owner = apiUser('validation-owner@executo.local');
    $admin = apiUser('validation-admin@executo.local', true);
    $district = apiDistrict($owner, 778);

    Sanctum::actingAs($admin, ['*']);

    $response = $this->postJson(sprintf('/api/v1/districts/%s/customers', $district->ulid), [
        'type' => 'legal',
        'email' => 'not-an-email',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'company_name']);
});

test('customer listing requires authentication', function (): void {
    $owner = apiUser('auth-owner@executo.local');
    $district = apiDistrict($owner, 779);

    $this->getJson(sprintf('/api/v1/districts/%s/customers', $district->ulid))
        ->assertUnauthorized();
});

test('nested customer route binding rejects customers from another district', function (): void {
    $owner = apiUser('binding-owner@executo.local');
    $admin = apiUser('binding-admin@executo.local', true);
    $firstDistrict = apiDistrict($owner, 780);
    $secondDistrict = apiDistrict($owner, 781);
    $customer = Customer::query()->create([
        'district_id' => $firstDistrict->id,
        'name' => 'Bound Customer',
        'type' => 'physical',
        'email' => 'bound@example.com',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $this->getJson(sprintf('/api/v1/districts/%s/customers/%s', $secondDistrict->ulid, $customer->ulid))
        ->assertNotFound();
});

test('nested debt route binding rejects debts from another customer', function (): void {
    $owner = apiUser('debt-binding-owner@executo.local');
    $admin = apiUser('debt-binding-admin@executo.local', true);
    $district = apiDistrict($owner, 782);
    $firstCustomer = Customer::query()->create([
        'district_id' => $district->id,
        'name' => 'First Customer',
        'type' => 'physical',
    ]);
    $secondCustomer = Customer::query()->create([
        'district_id' => $district->id,
        'name' => 'Second Customer',
        'type' => 'physical',
    ]);
    $debt = Debt::query()->create([
        'district_id' => $district->id,
        'customer_id' => $firstCustomer->id,
        'amount' => '100.0000',
        'date' => '2026-04-01',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $this->getJson(sprintf(
        '/api/v1/districts/%s/customers/%s/debts/%s',
        $district->ulid,
        $secondCustomer->ulid,
        $debt->ulid,
    ))->assertNotFound();
});

test('nested payment route binding rejects payments from another debt', function (): void {
    $owner = apiUser('payment-binding-owner@executo.local');
    $admin = apiUser('payment-binding-admin@executo.local', true);
    $district = apiDistrict($owner, 783);
    $customer = Customer::query()->create([
        'district_id' => $district->id,
        'name' => 'Payment Customer',
        'type' => 'physical',
    ]);
    $firstDebt = Debt::query()->create([
        'district_id' => $district->id,
        'customer_id' => $customer->id,
        'amount' => '100.0000',
        'date' => '2026-04-01',
    ]);
    $secondDebt = Debt::query()->create([
        'district_id' => $district->id,
        'customer_id' => $customer->id,
        'amount' => '200.0000',
        'date' => '2026-04-02',
    ]);
    $payment = Payment::query()->create([
        'customer_id' => $customer->id,
        'debt_id' => $firstDebt->id,
        'amount' => '25.0000',
        'date' => '2026-04-03',
    ]);

    Sanctum::actingAs($admin, ['*']);

    $this->getJson(sprintf(
        '/api/v1/districts/%s/customers/%s/debts/%s/payments/%s',
        $district->ulid,
        $customer->ulid,
        $secondDebt->ulid,
        $payment->ulid,
    ))->assertNotFound();
});
