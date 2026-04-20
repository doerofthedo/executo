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

final class DistrictController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', District::class);

        return DistrictResource::collection(
            District::query()->orderBy('number')->get(),
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

        return response()->json([
            'district' => (new DistrictResource($district))->resolve(),
            'users_count' => $district->users()->count(),
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

        $data = District::query()
            ->orderBy('number')
            ->get()
            ->map(static function (District $district): array {
                return [
                    'district' => (new DistrictResource($district))->resolve(),
                    'users_count' => $district->users()->count(),
                    'customers_count' => Customer::query()->where('district_id', $district->id)->count(),
                    'debts_count' => Debt::query()->where('district_id', $district->id)->count(),
                    'payments_count' => Payment::query()
                        ->whereHas('debt', static fn ($query) => $query->where('district_id', $district->id))
                        ->count(),
                ];
            })
            ->values();

        return response()->json($data);
    }
}
