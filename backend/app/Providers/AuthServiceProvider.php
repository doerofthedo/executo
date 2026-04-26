<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Debtor;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use App\Models\User;
use App\Policies\DebtorPolicy;
use App\Policies\DebtPolicy;
use App\Policies\DistrictPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Debtor::class => DebtorPolicy::class,
        Debt::class => DebtPolicy::class,
        District::class => DistrictPolicy::class,
        Payment::class => PaymentPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('login', static fn(?User $user = null): bool => true);
        Gate::define('logout', static fn(User $user): bool => true);
        Gate::define('register', static fn(?User $user = null): bool => true);
        Gate::define('viewSelf', static fn(User $user): bool => true);
        Gate::define('requestEmailVerification', static fn(?User $user = null): bool => true);
        Gate::define('verifyEmail', static fn(?User $currentUser = null, ?User $user = null): bool => true);
        Gate::define('requestPasswordReset', static fn(?User $user = null): bool => true);
        Gate::define('resetPassword', static fn(?User $user = null): bool => true);

        Gate::before(static function ($user, string $ability): bool|null {
            if ($user->hasRole('app.admin')) {
                return true;
            }

            return null;
        });
    }
}
