<?php

declare(strict_types=1);

use App\Http\Middleware\AuditLog;
use App\Http\Middleware\CheckApiKey;
use App\Http\Middleware\DistrictScope;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: '/api/v1',
    )
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'audit.log' => AuditLog::class,
            'api.key' => CheckApiKey::class,
            'district.scope' => DistrictScope::class,
            'force.json' => ForceJsonResponse::class,
        ]);
        $middleware->api(prepend: [ForceJsonResponse::class]);
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(static fn () => true);
    })
    ->create();
