<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmptyResource extends JsonResource
{
    public static $wrap = null;

    public function __construct()
    {
        parent::__construct(null);
    }

    /**
     * @return array<string, never>
     */
    public function toArray(Request $request): array
    {
        return [];
    }
}
