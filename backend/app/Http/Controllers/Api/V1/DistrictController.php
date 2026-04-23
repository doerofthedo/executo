<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\District\Actions\CanViewDistrictUsersCountAction;
use App\Domain\District\Actions\ListAccessibleDistrictsAction;
use App\Domain\District\Actions\StoreDistrictAction;
use App\Domain\District\Actions\UpdateDistrictAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\District\StoreDistrictRequest;
use App\Http\Requests\District\UpdateDistrictRequest;
use App\Http\Resources\DistrictResource;
use App\Http\Resources\DistrictStatsResource;
use App\Http\Resources\DistrictStatsSummaryResource;
use App\Models\District;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DistrictController extends Controller
{
    public function __construct(
        private readonly ListAccessibleDistrictsAction $listAccessibleDistricts,
        private readonly StoreDistrictAction $storeDistrict,
        private readonly UpdateDistrictAction $updateDistrict,
        private readonly CanViewDistrictUsersCountAction $canViewDistrictUsersCount,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', District::class);
        $user = request()->user();

        if ($user === null) {
            abort(403);
        }

        return DistrictResource::collection(
            $this->listAccessibleDistricts->execute($user),
        );
    }

    public function store(StoreDistrictRequest $request): DistrictResource
    {
        $this->authorize('create', District::class);

        $district = $this->storeDistrict->execute($request->validated());

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

        return new DistrictResource($this->updateDistrict->execute($district, $request->validated()));
    }

    public function stats(District $district): DistrictStatsResource
    {
        $this->authorize('view', $district);

        $user = request()->user();

        return new DistrictStatsResource(
            $district,
            $user !== null && $this->canViewDistrictUsersCount->execute($user, $district),
        );
    }

    public function statsAll(): DistrictStatsSummaryResource
    {
        $this->authorize('viewAny', District::class);
        $user = request()->user();

        if ($user === null) {
            abort(403);
        }

        $data = $this->listAccessibleDistricts->execute($user)
            ->map(function (District $district) use ($user): array {
                return (new DistrictStatsResource(
                    $district,
                    $this->canViewDistrictUsersCount->execute($user, $district),
                ))->resolve(request());
            })
            ->values();

        return new DistrictStatsSummaryResource($data);
    }
}
