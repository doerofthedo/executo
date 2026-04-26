<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\DTOs\CustomerData;
use App\Models\Customer;
use App\Models\District;

final readonly class CreateCustomerAction
{
    public function execute(District $district, CustomerData $data): Customer
    {
        return Customer::query()->create([
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
