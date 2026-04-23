<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\CurrentUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class CurrentUserController extends Controller
{
    public function __invoke(Request $request): CurrentUserResource
    {
        Gate::authorize('viewSelf');

        return new CurrentUserResource($request->user());
    }
}
