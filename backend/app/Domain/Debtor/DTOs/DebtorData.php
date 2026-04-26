<?php

declare(strict_types=1);

namespace App\Domain\Debtor\DTOs;

use Spatie\LaravelData\Data;

final class DebtorData extends Data
{
    public function __construct(
        public string $name,
        public ?string $caseNumber,
        public string $type,
        public ?string $email,
        public ?string $phone,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $personalCode,
        public ?string $dateOfBirth,
        public ?string $companyName,
        public ?string $registrationNumber,
        public ?string $contactPerson,
    ) {}
}
