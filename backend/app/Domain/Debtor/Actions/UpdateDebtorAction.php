<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Actions;

use App\Models\Debtor;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class UpdateDebtorAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(Debtor $debtor, array $payload): Debtor
    {
        $debtor->fill($this->normalizePayload($payload, $debtor));

        if ($debtor->isDirty()) {
            $debtor->save();
        }

        $freshDebtor = $debtor->fresh();

        if ($freshDebtor === null) {
            throw new NotFoundHttpException();
        }

        return $freshDebtor;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizePayload(array $payload, ?Debtor $debtor = null): array
    {
        unset($payload['restore']);

        $type = $payload['type'] ?? $debtor?->type;

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
