<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthSessionResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        User $resource,
        private readonly string $token,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array{token: string, user: array<string, bool|string|null>}
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'user' => (new CurrentUserResource($this->resource))->resolve($request),
        ];
    }
}
