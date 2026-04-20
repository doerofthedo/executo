<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\DistrictController;
use App\Http\Controllers\Api\V1\DebtController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\Auth\AuthSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
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

    Route::post('/auth/email/verification-notification', [EmailVerificationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.email.verification-notification');

    Route::get('/districts/stats', [DistrictController::class, 'statsAll'])
        ->name('api.v1.districts.stats-all');

    Route::get('/districts', [DistrictController::class, 'index'])
        ->name('api.v1.districts.index');

    Route::post('/districts', [DistrictController::class, 'store'])
        ->name('api.v1.districts.store');

    Route::get('/districts/{district}', [DistrictController::class, 'show'])
        ->name('api.v1.districts.show');

    Route::patch('/districts/{district}', [DistrictController::class, 'update'])
        ->name('api.v1.districts.update');

    Route::get('/districts/{district}/stats', [DistrictController::class, 'stats'])
        ->name('api.v1.districts.stats');

    Route::get('/districts/{district}/customers/{customer}/debts/{debt}/payments', [PaymentController::class, 'index'])
        ->name('api.v1.payments.index');

    Route::post('/districts/{district}/customers/{customer}/debts/{debt}/payments', [PaymentController::class, 'store'])
        ->name('api.v1.payments.store');

    Route::get('/districts/{district}/customers/{customer}/debts/{debt}/payments/{payment}', [PaymentController::class, 'show'])
        ->name('api.v1.payments.show');

    Route::patch('/districts/{district}/customers/{customer}/debts/{debt}/payments/{payment}', [PaymentController::class, 'update'])
        ->name('api.v1.payments.update');

    Route::delete('/districts/{district}/customers/{customer}/debts/{debt}/payments/{payment}', [PaymentController::class, 'destroy'])
        ->name('api.v1.payments.destroy');

    Route::get('/districts/{district}/customers/{customer}/debts', [DebtController::class, 'index'])
        ->name('api.v1.debts.index');

    Route::post('/districts/{district}/customers/{customer}/debts', [DebtController::class, 'store'])
        ->name('api.v1.debts.store');

    Route::get('/districts/{district}/customers/{customer}/debts/{debt}', [DebtController::class, 'show'])
        ->name('api.v1.debts.show');

    Route::patch('/districts/{district}/customers/{customer}/debts/{debt}', [DebtController::class, 'update'])
        ->name('api.v1.debts.update');

    Route::delete('/districts/{district}/customers/{customer}/debts/{debt}', [DebtController::class, 'destroy'])
        ->name('api.v1.debts.destroy');
});

Route::get('/auth/email/verify/{user}/{hash}', [EmailVerificationController::class, 'update'])
    ->middleware(['signed', 'throttle:5,1'])
    ->name('api.v1.auth.email.verify');
