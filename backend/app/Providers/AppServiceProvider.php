<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        $publicPath = base_path('../public');
        $resolvedPublicPath = realpath($publicPath);

        $this->app->usePublicPath(
            is_string($resolvedPublicPath) ? $resolvedPublicPath : $publicPath,
        );

        Vite::useBuildDirectory('assets');
    }
}
