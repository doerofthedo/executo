<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DistrictResource extends JsonResource
{
    /**
     * @return array<string, bool|int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->resource->ulid,
            'number' => (int) $this->resource->number,
            'bailiff_name' => $this->resource->bailiff_name,
            'bailiff_surname' => $this->resource->bailiff_surname,
            'court' => $this->resource->court,
            'address' => $this->resource->address,
            'disabled' => (bool) $this->resource->disabled,
            'owner_ulid' => $this->resource->owner?->ulid,
        ];
    }
}
