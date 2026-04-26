<?php

declare(strict_types=1);

namespace App\Domain\Debtor\Actions;

use App\Domain\Debtor\Services\DebtorSearchService;
use App\Models\Debtor;
use App\Models\District;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListDebtorsAction
{
    public function __construct(
        private DebtorSearchService $debtorSearch,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Debtor>
     */
    public function execute(District $district, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $search = isset($filters['search'])
            ? $this->debtorSearch->normaliseQuery($filters['search'])
            : null;

        return Debtor::query()
            ->with(['district'])
            ->where('district_id', $district->id)
            ->when(
                ($filters['include_trashed'] ?? false) === true,
                static fn($query) => $query->withTrashed(),
            )
            ->when(
                isset($filters['type']),
                static fn($query) => $query->where('type', $filters['type']),
            )
            ->when($search !== null && $search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $like = '%' . $search . '%';

                    $nestedQuery
                        ->where('name', 'like', $like)
                        ->orWhere('case_number', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('personal_code', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('registration_number', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
