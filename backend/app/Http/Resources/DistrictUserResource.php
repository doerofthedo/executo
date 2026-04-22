<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DistrictUserResource extends JsonResource
{
    /**
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->resource?->ulid,
            'email' => $this->resource?->email,
            'name' => $this->resource?->name,
            'surname' => $this->resource?->surname,
            'role' => $this->resource?->district_role,
        ];
    }
}
