<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use Illuminate\Http\Request;

final readonly class LogoutUserAction
{
    public function execute(Request $request): void
    {
        $request->user()?->currentAccessToken()?->delete();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
