<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

final class DistrictStatsSummaryResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cards = $this->resource instanceof Collection
            ? $this->resource->values()
            : new Collection();

        /** @var Collection<int, array<string, mixed>> $cards */

        return [
            'data' => $cards->all(),
            'districts_count' => $cards->count(),
            'debtors_count' => $cards->sum('debtors_count'),
            'debts_count' => $cards->sum('debts_count'),
            'payments_count' => $cards->sum('payments_count'),
        ];
    }
}
