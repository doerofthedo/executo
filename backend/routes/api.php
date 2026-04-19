<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(static function (): void {
    Route::post('/auth/login', [AuthSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.login');
});

Route::middleware(['auth:sanctum'])->group(static function (): void {
    Route::get('/auth/me', CurrentUserController::class)
        ->name('api.v1.auth.me');

    Route::post('/auth/logout', [AuthSessionController::class, 'destroy'])
        ->name('api.v1.auth.logout');
});
