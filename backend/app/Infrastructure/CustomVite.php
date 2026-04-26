<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Illuminate\Foundation\Vite as BaseVite;

final class CustomVite extends BaseVite
{
    protected function manifestPath($buildDirectory): string
    {
        return base_path('../frontend/dist/manifest.json');
    }
}
