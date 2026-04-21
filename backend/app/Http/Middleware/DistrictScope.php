<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\District;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DistrictScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $district = $request->route('district');
        $user = $request->user();

        if (! $district instanceof District || $user === null || $user->hasRole('app.admin')) {
            return $next($request);
        }

        $hasAccess = $district->owner_id === $user->id
            || $user->districts()->where('districts.id', $district->id)->exists();

        if (! $hasAccess) {
            throw new NotFoundHttpException();
        }

        $request->attributes->set('current_district_id', $district->id);

        return $next($request);
    }
}
