<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DebtorResource extends JsonResource
{
    /**
     * @return array<string, bool|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->resource->ulid,
            'district_ulid' => $this->resource->district?->ulid,
            'name' => $this->resource->name,
            'case_number' => $this->resource->case_number,
            'type' => $this->resource->type,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'personal_code' => $this->resource->personal_code,
            'date_of_birth' => $this->resource->date_of_birth?->toDateString(),
            'company_name' => $this->resource->company_name,
            'registration_number' => $this->resource->registration_number,
            'contact_person' => $this->resource->contact_person,
            'is_deleted' => $this->resource->trashed(),
            'deleted_at' => $this->resource->deleted_at?->toAtomString(),
        ];
    }
}
