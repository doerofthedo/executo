<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/{path?}', 'spa')
    ->where('path', '^(?!api|assets).*$');
