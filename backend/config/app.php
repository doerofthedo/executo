<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Executo'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'lv'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => 'lv_LV',
];
