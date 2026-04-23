<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserProfileResource extends JsonResource
{
    /**
     * @return array<string, string|bool|int|null|array<string, string|int|null>>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->resource?->ulid,
            'email' => $this->resource?->email,
            'name' => $this->resource?->name,
            'surname' => $this->resource?->surname,
            'disabled' => $this->resource->disabled ?? false,
            'is_email_verified' => $this->resource?->hasVerifiedEmail() ?? false,
            'email_verified_at' => $this->resource?->email_verified_at?->toAtomString(),
            'preferences' => [
                'default_district_ulid' => $this->resource?->preference?->defaultDistrict?->ulid,
                'locale' => $this->resource?->preference?->locale,
                'timezone' => $this->resource?->preference?->timezone ?? 'Europe/Riga',
                'date_format' => $this->resource?->preference?->date_format,
                'decimal_separator' => $this->resource?->preference?->decimal_separator,
                'thousand_separator' => $this->resource?->preference?->thousand_separator,
                'table_page_size' => $this->resource?->preference?->table_page_size,
            ],
        ];
    }
}
