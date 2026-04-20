<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CurrentUserResource extends JsonResource
{
    /**
     * @return array<string, string|bool|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->resource?->ulid,
            'email' => $this->resource?->email,
            'name' => $this->resource?->name,
            'disabled' => $this->resource?->disabled ?? false,
            'is_email_verified' => $this->resource?->hasVerifiedEmail() ?? false,
            'email_verified_at' => $this->resource?->email_verified_at?->toAtomString(),
        ];
    }
}
