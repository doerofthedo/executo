<?php

declare(strict_types=1);

namespace App\Domain\Debt\DTOs;

use Spatie\LaravelData\Data;

final class InterestScheduleData extends Data
{
    /**
     * @param  array<int, InterestScheduleRowData>  $rows
     */
    public function __construct(
        public array $rows,
        public InterestScheduleRowData $totalRow,
    ) {
    }
}
