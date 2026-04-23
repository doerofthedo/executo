<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPreference;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

final class SampleDataSeeder extends Seeder
{
    /**
     * @var array<int, array{first: string, last: string}>
     */
    private const PEOPLE = [
        ['first' => 'Ilze', 'last' => 'Kalniņa'],
        ['first' => 'Jānis', 'last' => 'Ozols'],
        ['first' => 'Līga', 'last' => 'Meža'],
        ['first' => 'Kaspars', 'last' => 'Bērziņš'],
        ['first' => 'Marta', 'last' => 'Vītola'],
        ['first' => 'Edgars', 'last' => 'Zariņš'],
        ['first' => 'Agnese', 'last' => 'Liepa'],
        ['first' => 'Andris', 'last' => 'Sproģis'],
    ];

    /**
     * @var array<int, string>
     */
    private const COMPANIES = [
        'SIA BaltTech Solutions',
        'SIA Rīgas Energo Serviss',
        'SIA Daugava Timber',
        'SIA MežRūpe Baltic',
        'SIA NordMetals',
        'SIA Kurzeme Logistics',
    ];

    public function run(): void
    {
        mt_srand(20260420);

        $districtAdminRole = Role::query()->where('name', 'district.admin')->firstOrFail();
        $districtUserRole = Role::query()->where('name', 'district.user')->firstOrFail();

        foreach ($this->districtDirectory() as $districtIndex => $districtData) {
            $districtAdmin = $this->upsertUser(
                email: $districtData['admin_email'],
                name: $districtData['admin_name'],
                surname: $districtData['admin_surname'],
                locale: 'lv',
                timezone: 'Europe/Riga',
                dateFormat: 'DD.MM.YYYY.',
                decimalSeparator: ',',
                thousandSeparator: ' ',
            );

            $district = District::query()->updateOrCreate(
                ['number' => $districtData['number']],
                [
                    'bailiff_name' => $districtData['admin_name'],
                    'bailiff_surname' => $districtData['admin_surname'],
                    'court' => $districtData['court'],
                    'address' => $districtData['address'],
                    'disabled' => false,
                    'owner_id' => $districtAdmin->id,
                ],
            );

            $district->setting()->updateOrCreate(
                ['district_id' => $district->id],
                [
                    'locale' => 'lv',
                    'date_format' => 'DD.MM.YYYY.',
                    'decimal_separator' => ',',
                    'thousand_separator' => ' ',
                ],
            );

            $district->users()->syncWithoutDetaching([
                $districtAdmin->id => ['role_id' => $districtAdminRole->id],
            ]);

            foreach ($this->districtUsersFor($districtIndex) as $userIndex => $userData) {
                $user = $this->upsertUser(
                    email: sprintf('district%d.user%d@executo.local', $districtData['number'], $userIndex + 1),
                    name: $userData['first'],
                    surname: $userData['last'],
                    locale: 'en',
                    timezone: 'Europe/Riga',
                    dateFormat: 'YYYY-MM-DD',
                    decimalSeparator: ',',
                    thousandSeparator: ' ',
                );

                $district->users()->syncWithoutDetaching([
                    $user->id => ['role_id' => $districtUserRole->id],
                ]);
            }

            if ($districtData['number'] === 69) {
                $this->pruneLegacyDistrictCustomers($district);
            } else {
                foreach ($this->customersForDistrict($district, $districtIndex) as $customer) {
                    $this->seedDebtsAndPayments($customer, $districtIndex);
                }
            }
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function districtUsersFor(int $districtIndex): Collection
    {
        return collect(range(0, 2))
            ->map(function (int $offset) use ($districtIndex): array {
                $person = self::PEOPLE[($districtIndex * 3 + $offset) % count(self::PEOPLE)];

                return $person;
            });
    }

    private function upsertUser(
        string $email,
        string $name,
        string $surname,
        string $locale,
        string $timezone,
        string $dateFormat,
        string $decimalSeparator,
        string $thousandSeparator,
    ): User {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'surname' => $surname,
                'disabled' => false,
                'email_verified_at' => now(),
                'password' => Hash::make('123'),
                'mfa_enabled' => false,
            ],
        );

        UserPreference::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'locale' => $locale,
                'timezone' => $timezone,
                'date_format' => $dateFormat,
                'decimal_separator' => $decimalSeparator,
                'thousand_separator' => $thousandSeparator,
                'table_page_size' => 25,
            ],
        );

        return $user;
    }

    /**
     * @return Collection<int, Customer>
     */
    private function customersForDistrict(District $district, int $districtIndex): Collection
    {
        $customers = collect();

        foreach (range(0, 3) as $offset) {
            if ($offset % 2 === 0) {
                $person = self::PEOPLE[($districtIndex * 2 + $offset) % count(self::PEOPLE)];
                $customers->push($this->upsertPhysicalCustomer($district, $person, $offset + 1));
                continue;
            }

            $company = self::COMPANIES[($districtIndex * 2 + $offset) % count(self::COMPANIES)];
            $customers->push($this->upsertLegalCustomer($district, $company, $offset + 1));
        }

        return $customers;
    }

    /**
     * @param  array{first: string, last: string}  $person
     */
    private function upsertPhysicalCustomer(District $district, array $person, int $sequence): Customer
    {
        $caseNumber = sprintf('%d/%03d/2026', 1000 + $sequence, $district->number);
        $email = sprintf(
            '%s.%s.%d@customer.executo.local',
            strtolower($person['first']),
            strtolower($person['last']),
            $district->number,
        );

        return Customer::query()->updateOrCreate(
            [
                'district_id' => $district->id,
                'case_number' => $caseNumber,
            ],
            [
                'name' => sprintf('%s %s', $person['first'], $person['last']),
                'type' => 'physical',
                'email' => $email,
                'phone' => sprintf('+3712%07d', 1000000 + ($district->number * 10) + $sequence),
                'first_name' => $person['first'],
                'last_name' => $person['last'],
                'personal_code' => sprintf('%06d-%05d', 120101 + $sequence, 10000 + $district->number),
                'date_of_birth' => CarbonImmutable::parse('1988-01-01')->addYears($sequence)->toDateString(),
                'company_name' => null,
                'registration_number' => null,
                'contact_person' => null,
            ],
        );
    }

    private function upsertLegalCustomer(District $district, string $companyName, int $sequence): Customer
    {
        $caseNumber = sprintf('%d/%03d/2026', 2000 + $sequence, $district->number);

        return Customer::query()->updateOrCreate(
            [
                'district_id' => $district->id,
                'case_number' => $caseNumber,
            ],
            [
                'name' => $companyName,
                'type' => 'legal',
                'email' => sprintf('office%d@company.executo.local', $district->number),
                'phone' => sprintf('+3716%07d', 2000000 + ($district->number * 10) + $sequence),
                'first_name' => null,
                'last_name' => null,
                'personal_code' => null,
                'date_of_birth' => null,
                'company_name' => $companyName,
                'registration_number' => sprintf('40%08d', ($district->number * 100) + $sequence),
                'contact_person' => self::PEOPLE[($district->number + $sequence) % count(self::PEOPLE)]['first'] . ' ' .
                    self::PEOPLE[($district->number + $sequence) % count(self::PEOPLE)]['last'],
            ],
        );
    }

    private function seedDebtsAndPayments(Customer $customer, int $districtIndex): void
    {
        Payment::query()->where('customer_id', $customer->id)->delete();
        Debt::query()->where('customer_id', $customer->id)->delete();

        foreach (range(1, 2) as $debtSequence) {
            $baseDate = CarbonImmutable::parse('2025-01-15')
                ->addMonths($districtIndex + $debtSequence)
                ->addDays($customer->id % 12);

            $debt = Debt::query()->create([
                'district_id' => $customer->district_id,
                'customer_id' => $customer->id,
                'amount' => $this->sampleAmount('450', (string) $debtSequence, '175.125'),
                'date' => $baseDate->toDateString(),
                'description' => sprintf('Sample debt %d for %s', $debtSequence, $customer->case_number ?? $customer->name ?? 'customer'),
            ]);

            foreach (range(1, 3) as $paymentSequence) {
                $paymentDate = $baseDate->addDays($paymentSequence * 21);

                Payment::query()->create([
                    'customer_id' => $customer->id,
                    'debt_id' => $debt->id,
                    'amount' => $this->sampleAmount('35', (string) $paymentSequence, '12.5'),
                    'date' => $paymentDate->toDateString(),
                    'description' => sprintf('Sample payment %d for debt %s', $paymentSequence, $debt->ulid),
                ]);
            }
        }
    }

    private function sampleAmount(string $base, string $sequence, string $step): string
    {
        return bcadd($base, bcmul($sequence, $step, 4), 4);
    }

    private function pruneLegacyDistrictCustomers(District $district): void
    {
        $payload = $this->legacyExportPayload();

        if ($payload === null) {
            return;
        }

        $validCaseNumbers = [];
        $validNamesWithoutCaseNumber = [];

        foreach ($payload['debtors'] ?? [] as $debtor) {
            if (! is_array($debtor)) {
                continue;
            }

            $caseNumber = $this->nullableString($debtor['case_number'] ?? null);
            $name = $this->nullableString($debtor['name'] ?? null);

            if ($caseNumber !== null) {
                $validCaseNumbers[] = $caseNumber;
                continue;
            }

            if ($name !== null) {
                $validNamesWithoutCaseNumber[] = $name;
            }
        }

        $customers = DB::table('customers')
            ->select('id', 'case_number', 'name')
            ->where('district_id', $district->id)
            ->get();

        $staleCustomerIds = [];

        foreach ($customers as $customer) {
            $caseNumber = is_string($customer->case_number) && $customer->case_number !== ''
                ? $customer->case_number
                : null;
            $name = is_string($customer->name) && $customer->name !== ''
                ? $customer->name
                : null;

            $isLegacyCustomer = $caseNumber !== null
                ? in_array($caseNumber, $validCaseNumbers, true)
                : ($name !== null && in_array($name, $validNamesWithoutCaseNumber, true));

            if (! $isLegacyCustomer && is_int($customer->id)) {
                $staleCustomerIds[] = $customer->id;
            }
        }

        if ($staleCustomerIds === []) {
            return;
        }

        DB::table('customers')
            ->whereIn('id', $staleCustomerIds)
            ->delete();
    }

    /**
     * @return Collection<int, array{number: int, court: string, address: string, admin_email: string, admin_name: string, admin_surname: string}>
     */
    private function districtDirectory(): Collection
    {
        $path = base_path('../data/lzti.json');

        if (! File::exists($path)) {
            throw new \RuntimeException('SampleDataSeeder requires data/lzti.json.');
        }

        /** @var array{districts?: array<int, array<string, mixed>>} $data */
        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return collect($data['districts'] ?? [])
            ->filter(static function (array $district): bool {
                $number = (int) ($district['district_number'] ?? 0);
                $type = mb_strtolower((string) ($district['district_type'] ?? ''));

                return $number === 69 || $type === 'test';
            })
            ->map(static function (array $district): array {
                $admin = is_array($district['admin'] ?? null) ? $district['admin'] : [];
                $number = (int) ($district['district_number'] ?? 0);

                return [
                    'number' => $number,
                    'court' => (string) ($district['court'] ?? ''),
                    'address' => (string) ($district['address'] ?? ''),
                    'admin_email' => $admin['email'] ?? sprintf('district%d.admin@executo.local', $number),
                    'admin_name' => $admin['first_name'] ?? (string) ($district['district_name'] ?? 'District'),
                    'admin_surname' => $admin['last_name'] ?? 'Administrator',
                ];
            })
            ->filter(static fn(array $district): bool => $district['number'] > 0)
            ->values();
    }

    private function legacyExportPayload(): ?array
    {
        $path = base_path('../data/export.json');

        if (! File::exists($path)) {
            return null;
        }

        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($payload) ? $payload : null;
    }

    private function nullableString(\Stringable|int|float|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
