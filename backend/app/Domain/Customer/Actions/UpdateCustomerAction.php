<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Models\Customer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdateCustomerAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(Customer $customer, array $payload): Customer
    {
        $customer->fill($this->normalizePayload($payload, $customer));

        if ($customer->isDirty()) {
            $customer->save();
        }

        $freshCustomer = $customer->fresh();

        if ($freshCustomer === null) {
            throw new NotFoundHttpException();
        }

        return $freshCustomer;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizePayload(array $payload, ?Customer $customer = null): array
    {
        unset($payload['restore']);

        $type = $payload['type'] ?? $customer?->type;

        if ($type === 'physical') {
            $firstName = $payload['first_name'] ?? null;
            $lastName = $payload['last_name'] ?? null;

            if (($payload['name'] ?? null) === null && ($firstName !== null || $lastName !== null)) {
                $payload['name'] = trim(implode(' ', array_filter([$firstName, $lastName])));
            }

            $payload['company_name'] = null;
            $payload['registration_number'] = null;
            $payload['contact_person'] = null;
        }

        if ($type === 'legal') {
            $companyName = $payload['company_name'] ?? null;

            if (($payload['name'] ?? null) === null && $companyName !== null) {
                $payload['name'] = $companyName;
            }

            $payload['first_name'] = null;
            $payload['last_name'] = null;
            $payload['personal_code'] = null;
            $payload['date_of_birth'] = null;
        }

        return $payload;
    }
}
