<?php

declare(strict_types=1);

define('LARAVEL_START', microtime(true));

require_once __DIR__ . '/../backend/vendor/autoload.php';

$app = require_once __DIR__ . '/../backend/bootstrap/app.php';

$app->bind(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class,
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture(),
)->send();

$kernel->terminate($request, $response);
