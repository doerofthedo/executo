<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

final readonly class AuthenticateUserAction
{
    public function execute(string $email, string $password): bool
    {
        return $email !== '' && $password !== '';
    }
}
