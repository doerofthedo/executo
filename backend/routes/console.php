<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:executo', static function (): void {
    $this->comment('Executo backend scaffold');
});
