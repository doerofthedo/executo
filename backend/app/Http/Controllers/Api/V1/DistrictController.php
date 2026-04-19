<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DistrictResource;
use App\Models\District;
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
}
