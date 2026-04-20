<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->name('login');

Route::view('/{path?}', 'app')
    ->where('path', '^(?!api|assets|login$).*$');
