<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $payload = $this->payload();

        if ($payload === null) {
            return;
        }

        DB::transaction(function () use ($payload): void {
            $ownerEmail = $this->ownerEmail($payload);
            $ownerUserId = $this->upsertUsers($payload['users_by_email'] ?? [], $ownerEmail);
            $districtId = $this->upsertDistrict($payload, $ownerUserId);

            $this->attachDistrictUsers($payload['users_by_email'] ?? [], $districtId, $ownerEmail);
            $this->upsertCustomers($payload['debtors'] ?? [], $districtId);
        });
    }

    public function down(): void
    {
        $payload = $this->payload();

        if ($payload === null) {
            return;
        }

        $districtNumber = $this->districtNumber($payload);

        DB::transaction(function () use ($payload, $districtNumber): void {
            $districtId = DB::table('districts')
                ->where('number', $districtNumber)
                ->value('id');

            if (! is_int($districtId)) {
                return;
            }

            foreach ($payload['debtors'] ?? [] as $debtor) {
                if (! is_array($debtor)) {
                    continue;
                }

                $customerId = $this->findExistingCustomerId($districtId, $debtor);

                if ($customerId !== null) {
                    DB::table('customers')->where('id', $customerId)->delete();
                }
            }

            $emails = array_keys($payload['users_by_email'] ?? []);

            if ($emails !== []) {
                DB::table('district_user')
                    ->where('district_id', $districtId)
                    ->whereIn('user_id', $this->userIdQuery($emails))
                    ->delete();
            }

            $remainingCustomers = DB::table('customers')
                ->where('district_id', $districtId)
                ->exists();

            if (! $remainingCustomers) {
                DB::table('districts')->where('id', $districtId)->delete();
            }
        });
    }

    private function payload(): ?array
    {
        $paths = [
            base_path('data/export.json'),
            dirname(base_path()) . '/data/export.json',
        ];

        $path = null;

        foreach ($paths as $candidate) {
            if (File::exists($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if ($path === null) {
            return null;
        }

        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new \RuntimeException('Legacy export payload is invalid.');
        }

        return $payload;
    }

    private function ownerEmail(array $payload): string
    {
        $emails = [];

        foreach ($payload['debtors'] ?? [] as $debtor) {
            if (! is_array($debtor)) {
                continue;
            }

            $email = $this->nullableString($debtor['user_email'] ?? null);

            if ($email !== null) {
                $emails[] = mb_strtolower($email);
            }
        }

        if ($emails === []) {
            $fallback = array_key_first($payload['users_by_email'] ?? []);

            if (! is_string($fallback) || $fallback === '') {
                throw new \RuntimeException('Could not infer legacy district owner email.');
            }

            return mb_strtolower($fallback);
        }

        $counts = array_count_values($emails);
        arsort($counts);

        return (string) array_key_first($counts);
    }

    private function districtNumber(array $payload): int
    {
        $numbers = [];

        foreach ($payload['debtors'] ?? [] as $debtor) {
            if (! is_array($debtor)) {
                continue;
            }

            $caseNumber = $this->nullableString($debtor['case_number'] ?? null);

            if ($caseNumber === null) {
                continue;
            }

            if (preg_match('/\/(\d{3})\//', $caseNumber, $matches) === 1) {
                $numbers[] = (int) $matches[1];
            }
        }

        if ($numbers === []) {
            return 69;
        }

        $counts = array_count_values($numbers);
        arsort($counts);

        return (int) array_key_first($counts);
    }

    private function upsertUsers(array $usersByEmail, string $ownerEmail): int
    {
        $ownerUserId = null;

        foreach ($usersByEmail as $email => $legacyUser) {
            if (! is_array($legacyUser) || ! is_string($email) || $email === '') {
                continue;
            }

            $normalizedEmail = mb_strtolower($email);
            [$name, $surname] = $this->splitLegacyName($normalizedEmail, $legacyUser);
            $existingUser = DB::table('users')
                ->where('email', $normalizedEmail)
                ->first(['ulid', 'created_at']);

            $values = [
                'ulid' => is_object($existingUser) && is_string($existingUser->ulid)
                    ? $existingUser->ulid
                    : (string) Str::ulid(),
                'name' => $name,
                'surname' => $surname,
                'email_verified_at' => $legacyUser['email_verified_at'] ?? null,
                'password' => $legacyUser['password'] ?? bcrypt(Str::random(32)),
                'remember_token' => $legacyUser['remember_token'] ?? null,
                'mfa_secret' => null,
                'mfa_enabled' => false,
                'created_at' => is_object($existingUser) && $existingUser->created_at !== null
                    ? $existingUser->created_at
                    : ($legacyUser['created_at'] ?? now()),
                'updated_at' => $legacyUser['updated_at'] ?? ($legacyUser['created_at'] ?? now()),
            ];

            if (Schema::hasColumn('users', 'disabled')) {
                $values['disabled'] = false;
            }

            DB::table('users')->updateOrInsert(
                ['email' => $normalizedEmail],
                $values,
            );

            $userId = DB::table('users')
                ->where('email', $normalizedEmail)
                ->value('id');

            if (! is_int($userId)) {
                throw new \RuntimeException(sprintf('Failed to resolve imported user id for %s.', $normalizedEmail));
            }

            if ($normalizedEmail === $ownerEmail) {
                $ownerUserId = $userId;
            }
        }

        if (! is_int($ownerUserId)) {
            throw new \RuntimeException(sprintf('Could not resolve imported district owner user %s.', $ownerEmail));
        }

        return $ownerUserId;
    }

    private function upsertDistrict(array $payload, int $ownerUserId): int
    {
        $districtNumber = $this->districtNumber($payload);
        $existingDistrict = DB::table('districts')
            ->where('number', $districtNumber)
            ->first(['ulid', 'created_at']);

        $values = [
            'ulid' => is_object($existingDistrict) && is_string($existingDistrict->ulid)
                ? $existingDistrict->ulid
                : (string) Str::ulid(),
            'owner_id' => $ownerUserId,
            'created_at' => is_object($existingDistrict) && $existingDistrict->created_at !== null
                ? $existingDistrict->created_at
                : now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('districts', 'court')) {
            $values['court'] = null;
        }

        if (Schema::hasColumn('districts', 'address')) {
            $values['address'] = null;
        }

        if (Schema::hasColumn('districts', 'disabled')) {
            $values['disabled'] = false;
        }

        DB::table('districts')->updateOrInsert(
            ['number' => $districtNumber],
            $values,
        );

        $districtId = DB::table('districts')
            ->where('number', $districtNumber)
            ->value('id');

        if (! is_int($districtId)) {
            throw new \RuntimeException(sprintf('Failed to resolve district %d.', $districtNumber));
        }

        return $districtId;
    }

    private function attachDistrictUsers(array $usersByEmail, int $districtId, string $ownerEmail): void
    {
        $districtAdminRoleId = $this->roleId('district.admin');
        $districtUserRoleId = $this->roleId('district.user');

        foreach ($usersByEmail as $email => $legacyUser) {
            if (! is_array($legacyUser) || ! is_string($email) || $email === '') {
                continue;
            }

            $userId = DB::table('users')
                ->where('email', mb_strtolower($email))
                ->value('id');

            if (! is_int($userId)) {
                continue;
            }

            DB::table('district_user')->updateOrInsert(
                [
                    'district_id' => $districtId,
                    'user_id' => $userId,
                    'role_id' => mb_strtolower($email) === $ownerEmail ? $districtAdminRoleId : $districtUserRoleId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function upsertCustomers(array $debtors, int $districtId): void
    {
        foreach ($debtors as $debtor) {
            if (! is_array($debtor)) {
                continue;
            }

            $existingCustomerId = $this->findExistingCustomerId($districtId, $debtor);

            if ($existingCustomerId !== null) {
                DB::table('customers')->where('id', $existingCustomerId)->delete();
            }

            $customerId = DB::table('customers')->insertGetId([
                'ulid' => (string) Str::ulid(),
                'district_id' => $districtId,
                'name' => $this->nullableString($debtor['name'] ?? null),
                'case_number' => $this->nullableString($debtor['case_number'] ?? null),
                'type' => $this->mapCustomerType($debtor),
                'email' => $this->nullableString($debtor['email'] ?? null),
                'phone' => $this->nullableString($debtor['phone'] ?? null),
                'first_name' => $this->nullableString($debtor['first_name'] ?? null),
                'last_name' => $this->nullableString($debtor['last_name'] ?? null),
                'personal_code' => $this->nullableString($debtor['personal_code'] ?? null),
                'date_of_birth' => $this->normalizeDate($debtor['date_of_birth'] ?? null),
                'company_name' => $this->nullableString($debtor['company_name'] ?? null),
                'registration_number' => $this->nullableString($debtor['registration_number'] ?? null),
                'contact_person' => $this->nullableString($debtor['contact_person'] ?? null),
                'created_at' => $debtor['created_at'] ?? now(),
                'updated_at' => $debtor['updated_at'] ?? ($debtor['created_at'] ?? now()),
            ]);

            foreach ($debtor['debts'] ?? [] as $debt) {
                if (! is_array($debt)) {
                    continue;
                }

                $debtId = DB::table('debts')->insertGetId([
                    'ulid' => (string) Str::ulid(),
                    'district_id' => $districtId,
                    'customer_id' => $customerId,
                    'amount' => $this->decimal($debt['amount'] ?? 0),
                    'date' => $this->normalizeDate($debt['date'] ?? null) ?? now()->toDateString(),
                    'description' => $this->nullableString($debt['description'] ?? null),
                    'created_at' => $debt['created_at'] ?? now(),
                    'updated_at' => $debt['updated_at'] ?? ($debt['created_at'] ?? now()),
                ]);

                foreach ($debt['payments'] ?? [] as $payment) {
                    if (! is_array($payment)) {
                        continue;
                    }

                    DB::table('payments')->insert([
                        'ulid' => (string) Str::ulid(),
                        'customer_id' => $customerId,
                        'debt_id' => $debtId,
                        'amount' => $this->decimal($payment['amount'] ?? 0),
                        'date' => $this->normalizeDate($payment['date'] ?? null) ?? now()->toDateString(),
                        'description' => $this->nullableString($payment['description'] ?? null),
                        'created_at' => $payment['created_at'] ?? now(),
                        'updated_at' => $payment['updated_at'] ?? ($payment['created_at'] ?? now()),
                    ]);
                }
            }
        }
    }

    private function findExistingCustomerId(int $districtId, array $debtor): ?int
    {
        $caseNumber = $this->nullableString($debtor['case_number'] ?? null);

        if ($caseNumber !== null) {
            $id = DB::table('customers')
                ->where('district_id', $districtId)
                ->where('case_number', $caseNumber)
                ->value('id');

            return is_int($id) ? $id : null;
        }

        $name = $this->nullableString($debtor['name'] ?? null);

        if ($name === null) {
            return null;
        }

        $id = DB::table('customers')
            ->where('district_id', $districtId)
            ->where('name', $name)
            ->value('id');

        return is_int($id) ? $id : null;
    }

    private function splitLegacyName(string $email, array $legacyUser): array
    {
        $name = $this->nullableString($legacyUser['name'] ?? null);

        if ($name === null) {
            return [Str::headline(Str::before($email, '@')), 'Imported'];
        }

        $parts = preg_split('/\s+/u', $name) ?: [];

        if (count($parts) > 1) {
            $surname = (string) array_pop($parts);
            $givenName = trim(implode(' ', $parts));

            return [
                $givenName !== '' ? $givenName : $name,
                $surname !== '' ? $surname : 'Imported',
            ];
        }

        return [$name, 'Imported'];
    }

    private function mapCustomerType(array $legacyDebtor): string
    {
        $legacyType = $this->nullableString($legacyDebtor['type'] ?? null);

        return match ($legacyType) {
            'legal', 'company', 'juridical' => 'legal',
            'individual', 'physical', 'person' => 'physical',
            default => $this->nullableString($legacyDebtor['company_name'] ?? null) !== null ||
                $this->nullableString($legacyDebtor['registration_number'] ?? null) !== null
                ? 'legal'
                : 'physical',
        };
    }

    private function decimal(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }

    private function normalizeDate(mixed $value): ?string
    {
        $date = $this->nullableString($value);

        return $date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            ? $date
            : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function roleId(string $roleName): int
    {
        $roleId = DB::table('roles')
            ->where('name', $roleName)
            ->value('id');

        if (! is_int($roleId)) {
            DB::table('roles')->updateOrInsert(
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
                [
                    'scope' => 'district',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->value('id');
        }

        if (! is_int($roleId)) {
            throw new \RuntimeException(sprintf('Required role %s was not found.', $roleName));
        }

        return $roleId;
    }

    /**
     * @param  array<int, string>  $emails
     */
    private function userIdQuery(array $emails): Builder
    {
        return DB::table('users')
            ->select('id')
            ->whereIn('email', array_map('mb_strtolower', $emails));
    }
};
