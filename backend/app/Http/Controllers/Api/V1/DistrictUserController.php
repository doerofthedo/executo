<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\District\Actions\DeleteDistrictUserAction;
use App\Domain\District\Actions\ListDistrictUsersAction;
use App\Domain\District\Actions\StoreDistrictUserAction;
use App\Domain\District\Actions\UpdateDistrictUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\District\StoreDistrictUserRequest;
use App\Http\Requests\District\UpdateDistrictUserRequest;
use App\Http\Resources\DistrictUserResource;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Resources\EmptyResource;
use Symfony\Component\HttpFoundation\Response;

final class DistrictUserController extends Controller
{
    public function __construct(
        private readonly ListDistrictUsersAction $listDistrictUsers,
        private readonly StoreDistrictUserAction $storeDistrictUser,
        private readonly UpdateDistrictUserAction $updateDistrictUser,
        private readonly DeleteDistrictUserAction $deleteDistrictUser,
    ) {
    }

    public function index(District $district): AnonymousResourceCollection
    {
        $this->authorize('viewUsers', $district);

        return DistrictUserResource::collection($this->listDistrictUsers->execute($district));
    }

    public function store(StoreDistrictUserRequest $request, District $district): JsonResponse
    {
        $this->authorize('manageUsers', $district);

        /** @var array{email: string, role: string} $payload */
        $payload = $request->validated();
        $result = $this->storeDistrictUser->execute($district, $payload);

        return (new DistrictUserResource($result['user']))
            ->response()
            ->setStatusCode($result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    public function update(UpdateDistrictUserRequest $request, District $district, User $user): DistrictUserResource
    {
        $this->authorize('manageUsers', $district);

        /** @var array{role: string} $payload */
        $payload = $request->validated();

        return new DistrictUserResource($this->updateDistrictUser->execute($district, $user, $payload));
    }

    public function destroy(District $district, User $user): JsonResponse
    {
        $this->authorize('manageUsers', $district);

        $this->deleteDistrictUser->execute($district, $user);

        return (new EmptyResource())
            ->response()
            ->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
