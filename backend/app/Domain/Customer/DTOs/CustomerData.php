<?php

declare(strict_types=1);

namespace App\Domain\Customer\DTOs;

use Spatie\LaravelData\Data;

final class CustomerData extends Data
{
    public function __construct(
        public string $name,
        public string $surname,
        public string $personalCode,
        public ?string $email,
        public ?string $phone,
    ) {
    }
}
