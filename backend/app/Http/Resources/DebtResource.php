<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DebtResource extends JsonResource
{
    /**
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->resource->ulid,
            'district_ulid' => $this->resource->district?->ulid,
            'debtor_ulid' => $this->resource->debtor?->ulid,
            'amount' => (string) $this->resource->amount,
            'date' => $this->resource->date?->toDateString(),
            'description' => $this->resource->description,
        ];
    }
}
