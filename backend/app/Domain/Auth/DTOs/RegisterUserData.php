<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use Spatie\LaravelData\Data;

final class RegisterUserData extends Data
{
    public function __construct(
        public string $name,
        public string $surname,
        public string $email,
        public string $password,
        public string $locale,
    ) {
    }
}
