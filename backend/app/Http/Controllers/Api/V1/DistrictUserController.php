<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\District\Actions\StoreDistrictUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\District\StoreDistrictUserRequest;
use App\Http\Resources\DistrictUserResource;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DistrictUserController extends Controller
{
    public function __construct(
        private readonly StoreDistrictUserAction $storeDistrictUser,
    ) {
    }

    public function store(StoreDistrictUserRequest $request, District $district): JsonResponse
    {
        $this->authorize('update', $district);

        /** @var array{email: string, role: string} $payload */
        $payload = $request->validated();
        $result = $this->storeDistrictUser->execute($district, $payload);

        return (new DistrictUserResource($result['user']))
            ->response()
            ->setStatusCode($result['created'] ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
