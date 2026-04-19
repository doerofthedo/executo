<?php

declare(strict_types=1);

return [
    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', 'executo.local,localhost,127.0.0.1')),
    'guard' => ['web'],
    'expiration' => null,
];
