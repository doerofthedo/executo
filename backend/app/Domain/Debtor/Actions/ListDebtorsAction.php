<?php

declare(strict_types=1);

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Services\CustomerSearchService;
use App\Models\Customer;
use App\Models\District;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListCustomersAction
{
    public function __construct(
        private CustomerSearchService $customerSearch,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Customer>
     */
    public function execute(District $district, array $filters): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 25);
        $search = isset($filters['search'])
            ? $this->customerSearch->normaliseQuery($filters['search'])
            : null;

        return Customer::query()
            ->with(['district'])
            ->where('district_id', $district->id)
            ->when(
                ($filters['include_trashed'] ?? false) === true,
                static fn ($query) => $query->withTrashed(),
            )
            ->when(
                isset($filters['type']),
                static fn ($query) => $query->where('type', $filters['type']),
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
