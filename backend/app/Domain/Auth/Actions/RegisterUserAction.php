<?php

declare(strict_types=1);

namespace App\Domain\Auth\Actions;

use App\Domain\Auth\DTOs\RegisterUserData;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\DB;

final readonly class RegisterUserAction
{
    public function execute(RegisterUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => $data->name,
                'surname' => $data->surname,
                'email' => $data->email,
                'password' => $data->password,
                'disabled' => false,
                'mfa_enabled' => false,
            ]);

            UserPreference::query()->create([
                'user_id' => $user->id,
                'locale' => $data->locale,
                'date_format' => 'DD.MM.YYYY.',
                'decimal_separator' => ',',
                'thousand_separator' => ' ',
                'table_page_size' => 25,
            ]);

            return $user;
        });
    }
}
