<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmailVerificationStatusResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        private readonly bool $verified,
        private readonly bool $sent = false,
    ) {
        parent::__construct(null);
    }

    /**
     * @return array{sent: bool, verified: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'sent' => $this->sent,
            'verified' => $this->verified,
        ];
    }
}
