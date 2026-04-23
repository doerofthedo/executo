<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\District\Services\DistrictPermissionResolver;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DistrictStatsResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        District $resource,
        private readonly bool $canViewUsersCount,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var District $district */
        $district = $this->resource;

        return [
            'district' => (new DistrictResource($district))->resolve($request),
            'users_count' => $this->canViewUsersCount ? $district->users()->distinct('users.id')->count('users.id') : null,
            'can_view_users_count' => $this->canViewUsersCount,
            'can_manage_users' => $this->canViewUsersCount,
            'can_create_customer' => $request->user() !== null
                && app(DistrictPermissionResolver::class)->hasPermission($request->user(), 'district.customer.create', $district->id),
            'can_create_debt' => $request->user() !== null
                && app(DistrictPermissionResolver::class)->hasPermission($request->user(), 'district.debt.create', $district->id),
            'can_create_payment' => $request->user() !== null
                && app(DistrictPermissionResolver::class)->hasPermission($request->user(), 'district.payment.create', $district->id),
            'customers_count' => Customer::query()->where('district_id', $district->id)->count(),
            'debts_count' => Debt::query()->where('district_id', $district->id)->count(),
            'payments_count' => Payment::query()
                ->whereHas('debt', static fn ($query) => $query->where('district_id', $district->id))
                ->count(),
        ];
    }
}
