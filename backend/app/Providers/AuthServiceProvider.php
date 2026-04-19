<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\District;
use App\Models\Payment;
use App\Policies\CustomerPolicy;
use App\Policies\DebtPolicy;
use App\Policies\DistrictPolicy;
use App\Policies\PaymentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Customer::class => CustomerPolicy::class,
        Debt::class => DebtPolicy::class,
        District::class => DistrictPolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(static function ($user, string $ability): bool|null {
            if ($user->hasRole('app.admin')) {
                return true;
            }

            return null;
        });
    }
}
