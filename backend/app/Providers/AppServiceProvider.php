<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Auth\Events\UserProfileUpdated;
use App\Domain\Auth\Listeners\NotifyAdminsOnProfileChange;
use App\Infrastructure\CustomVite;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $this->app->singleton(Vite::class, fn () => new CustomVite());

        ViteFacade::useBuildDirectory('assets');

        Event::listen(UserProfileUpdated::class, NotifyAdminsOnProfileChange::class);
    }
}
