<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->name('login');
Route::view('/register', 'login')->name('register');
Route::view('/forgot-password', 'login')->name('forgot-password');
Route::view('/reset-password', 'login')->name('reset-password');

Route::view('/{path?}', 'app')
    ->where('path', '^(?!api|assets|login$|register$|forgot-password$|reset-password$).*$');
