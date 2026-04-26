<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\DistrictController;
use App\Http\Controllers\Api\V1\DistrictUserController;
use App\Http\Controllers\Api\V1\DebtController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\DebtorController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthSessionController;
use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\EmailVerificationController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Auth\RegistrationController;
use App\Models\Debtor;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::bind('debtor', static function (string $value): Debtor {
    $district = request()->route('district');
    $district = is_string($district)
        ? District::query()->where('ulid', $district)->first()
        : $district;
    $query = Debtor::query()
        ->where('ulid', $value);

    if ($district instanceof District) {
        $query->where('district_id', $district->id);
    }

    if ((request()->boolean('restore') && request()->isMethod('patch')) || request()->boolean('force')) {
        $query->withTrashed();
    }

    return $query->firstOrFail();
});

Route::bind('debt', static function (string $value): Debt {
    $district = request()->route('district');
    $district = is_string($district)
        ? District::query()->where('ulid', $district)->first()
        : $district;
    $debtor = request()->route('debtor');
    $debtor = is_string($debtor)
        ? Debtor::query()->where('ulid', $debtor)->first()
        : $debtor;

    $query = Debt::query()
        ->where('ulid', $value);

    if ($district instanceof District) {
        $query->where('district_id', $district->id);
    }

    if ($debtor instanceof Debtor) {
        $query->where('debtor_id', $debtor->id);
    }

    return $query->firstOrFail();
});

Route::bind('payment', static function (string $value): Payment {
    $debtor = request()->route('debtor');
    $debtor = is_string($debtor)
        ? Debtor::query()->where('ulid', $debtor)->first()
        : $debtor;
    $debt = request()->route('debt');
    $debt = is_string($debt)
        ? Debt::query()->where('ulid', $debt)->first()
        : $debt;

    $query = Payment::query()
        ->where('ulid', $value);

    if ($debtor instanceof Debtor) {
        $query->where('debtor_id', $debtor->id);
    }

    if ($debt instanceof Debt) {
        $query->where('debt_id', $debt->id);
    }

    return $query->firstOrFail();
});

Route::bind('user', static function (string $value): User {
    $query = User::query()
        ->where('ulid', $value);

    $district = request()->route('district');
    $routeName = request()->route()?->getName() ?? '';

    if ($district instanceof District && str_starts_with($routeName, 'api.v1.district-users.')) {
        $query->whereHas('districts', static fn($districtsQuery) => $districtsQuery->where('districts.id', $district->id));
    }

    return $query->firstOrFail();
});

Route::middleware('guest')->group(static function (): void {
    Route::post('/auth/login', [AuthSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.login');

    Route::post('/auth/register', [RegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.register');

    Route::post('/auth/email/verification-request', [RegistrationController::class, 'requestVerification'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.email.verification-request');

    Route::post('/auth/password/forgot', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.password.forgot');

    Route::post('/auth/password/reset', [PasswordResetController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('api.v1.auth.password.reset');
});

Route::post('/auth/email/verify-token', [EmailVerificationController::class, 'verifyToken'])
    ->middleware('throttle:5,1')
    ->name('api.v1.auth.email.verify-token');

Route::middleware(['auth:sanctum', 'district.scope'])->group(static function (): void {
    Route::get('/auth/me', CurrentUserController::class)
        ->name('api.v1.auth.me');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('api.v1.notifications.index');

    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->name('api.v1.notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('api.v1.notifications.read-all');

    Route::post('/auth/logout', [AuthSessionController::class, 'destroy'])
        ->name('api.v1.auth.logout');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->name('api.v1.users.show');

    Route::patch('/users/{user}', [UserController::class, 'update'])
        ->name('api.v1.users.update');

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

    Route::get('/districts/{district}/users', [DistrictUserController::class, 'index'])
        ->name('api.v1.district-users.index');

    Route::post('/districts/{district}/users', [DistrictUserController::class, 'store'])
        ->name('api.v1.district-users.store');

    Route::patch('/districts/{district}/users/{user}', [DistrictUserController::class, 'update'])
        ->name('api.v1.district-users.update');

    Route::delete('/districts/{district}/users/{user}', [DistrictUserController::class, 'destroy'])
        ->name('api.v1.district-users.destroy');

    Route::get('/districts/{district}/debtors', [DebtorController::class, 'index'])
        ->name('api.v1.debtors.index');

    Route::post('/districts/{district}/debtors', [DebtorController::class, 'store'])
        ->name('api.v1.debtors.store');

    Route::get('/districts/{district}/debtors/{debtor}', [DebtorController::class, 'show'])
        ->name('api.v1.debtors.show');

    Route::patch('/districts/{district}/debtors/{debtor}', [DebtorController::class, 'update'])
        ->name('api.v1.debtors.update');

    Route::delete('/districts/{district}/debtors/{debtor}', [DebtorController::class, 'destroy'])
        ->name('api.v1.debtors.destroy');

    Route::get('/districts/{district}/debtors/{debtor}/debts/{debt}/payments', [PaymentController::class, 'index'])
        ->name('api.v1.payments.index');

    Route::post('/districts/{district}/debtors/{debtor}/debts/{debt}/payments', [PaymentController::class, 'store'])
        ->name('api.v1.payments.store');

    Route::get('/districts/{district}/debtors/{debtor}/debts/{debt}/payments/{payment}', [PaymentController::class, 'show'])
        ->name('api.v1.payments.show');

    Route::patch('/districts/{district}/debtors/{debtor}/debts/{debt}/payments/{payment}', [PaymentController::class, 'update'])
        ->name('api.v1.payments.update');

    Route::delete('/districts/{district}/debtors/{debtor}/debts/{debt}/payments/{payment}', [PaymentController::class, 'destroy'])
        ->name('api.v1.payments.destroy');

    Route::get('/districts/{district}/debtors/{debtor}/debts', [DebtController::class, 'index'])
        ->name('api.v1.debts.index');

    Route::post('/districts/{district}/debtors/{debtor}/debts', [DebtController::class, 'store'])
        ->name('api.v1.debts.store');

    Route::get('/districts/{district}/debtors/{debtor}/debts/{debt}', [DebtController::class, 'show'])
        ->name('api.v1.debts.show');

    Route::patch('/districts/{district}/debtors/{debtor}/debts/{debt}', [DebtController::class, 'update'])
        ->name('api.v1.debts.update');

    Route::delete('/districts/{district}/debtors/{debtor}/debts/{debt}', [DebtController::class, 'destroy'])
        ->name('api.v1.debts.destroy');
});

Route::get('/auth/email/verify/{user}/{hash}', [EmailVerificationController::class, 'update'])
    ->middleware(['signed', 'throttle:5,1'])
    ->name('api.v1.auth.email.verify');
