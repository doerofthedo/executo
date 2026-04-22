<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\District\StoreDistrictRequest;
use App\Http\Requests\District\UpdateDistrictRequest;
use App\Http\Resources\DistrictResource;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Role;

final class DistrictController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', District::class);

        $query = District::query()
            ->orderBy('number');

        if (! request()->user()?->hasRole('app.admin')) {
            $query->where(function ($builder): void {
                $builder
                    ->where('owner_id', request()->user()?->id)
                    ->orWhereHas('users', static fn ($usersQuery) => $usersQuery->where('users.id', request()->user()?->id));
            });
        }

        return DistrictResource::collection(
            $query->get(),
        );
    }

    public function store(StoreDistrictRequest $request): DistrictResource
    {
        $this->authorize('create', District::class);

        $district = District::query()->create($request->validated());

        return new DistrictResource($district);
    }

    public function show(District $district): DistrictResource
    {
        $this->authorize('view', $district);

        return new DistrictResource($district);
    }

    public function update(UpdateDistrictRequest $request, District $district): DistrictResource
    {
        $this->authorize('update', $district);

        $district->fill($request->validated());

        if ($district->isDirty()) {
            $district->save();
        }

        return new DistrictResource($district->fresh());
    }

    public function stats(District $district): JsonResponse
    {
        $this->authorize('view', $district);

        $canViewUsersCount = $this->canViewUsersCount($district);

        return response()->json([
            'district' => (new DistrictResource($district))->resolve(),
            'users_count' => $canViewUsersCount ? $district->users()->count() : null,
            'can_view_users_count' => $canViewUsersCount,
            'can_manage_users' => $canViewUsersCount,
            'can_create_customer' => request()->user()?->can('district.customer.create') ?? false,
            'can_create_debt' => request()->user()?->can('district.debt.create') ?? false,
            'can_create_payment' => request()->user()?->can('district.payment.create') ?? false,
            'customers_count' => Customer::query()->where('district_id', $district->id)->count(),
            'debts_count' => Debt::query()->where('district_id', $district->id)->count(),
            'payments_count' => Payment::query()
                ->whereHas('debt', static fn ($query) => $query->where('district_id', $district->id))
                ->count(),
        ]);
    }

    public function statsAll(): JsonResponse
    {
        $this->authorize('viewAny', District::class);

        $query = District::query()
            ->orderBy('number');

        if (! request()->user()?->hasRole('app.admin')) {
            $query->where(function ($builder): void {
                $builder
                    ->where('owner_id', request()->user()?->id)
                    ->orWhereHas('users', static fn ($usersQuery) => $usersQuery->where('users.id', request()->user()?->id));
            });
        }

        $data = $query->get()
            ->map(function (District $district): array {
                $canViewUsersCount = $this->canViewUsersCount($district);

                return [
                    'district' => (new DistrictResource($district))->resolve(),
                    'users_count' => $canViewUsersCount ? $district->users()->count() : null,
                    'can_view_users_count' => $canViewUsersCount,
                    'can_manage_users' => $canViewUsersCount,
                    'can_create_customer' => request()->user()?->can('district.customer.create') ?? false,
                    'can_create_debt' => request()->user()?->can('district.debt.create') ?? false,
                    'can_create_payment' => request()->user()?->can('district.payment.create') ?? false,
                    'customers_count' => Customer::query()->where('district_id', $district->id)->count(),
                    'debts_count' => Debt::query()->where('district_id', $district->id)->count(),
                    'payments_count' => Payment::query()
                        ->whereHas('debt', static fn ($query) => $query->where('district_id', $district->id))
                        ->count(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $data,
            'districts_count' => $data->count(),
            'customers_count' => $data->sum('customers_count'),
            'debts_count' => $data->sum('debts_count'),
            'payments_count' => $data->sum('payments_count'),
        ]);
    }

    private function canViewUsersCount(District $district): bool
    {
        $user = request()->user();

        if ($user === null) {
            return false;
        }

        if ($user->hasRole('app.admin') || $district->owner_id === $user->id) {
            return true;
        }

        $districtAdminRoleId = Role::query()
            ->where('name', 'district.admin')
            ->where('guard_name', 'web')
            ->value('id');

        if (! is_int($districtAdminRoleId)) {
            return false;
        }

        return $district->users()
            ->where('users.id', $user->id)
            ->wherePivot('role_id', $districtAdminRoleId)
            ->exists();
    }
}
