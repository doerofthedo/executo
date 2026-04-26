<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Actions;

use App\Domain\Debtor\DTOs\DebtorData;
use App\Models\Debtor;
use App\Models\District;

final readonly class CreateDebtorAction
{
    public function execute(District $district, DebtorData $data): Debtor
    {
        return Debtor::query()->create([
            'district_id' => $district->id,
            'name' => $data->name,
            'case_number' => $data->caseNumber,
            'type' => $data->type,
            'email' => $data->email,
            'phone' => $data->phone,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'personal_code' => $data->personalCode,
            'date_of_birth' => $data->dateOfBirth,
            'company_name' => $data->companyName,
            'registration_number' => $data->registrationNumber,
            'contact_person' => $data->contactPerson,
        ]);
    }
}
