<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string|\Illuminate\Validation\Rules\Password>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email:rfc', 'max:255'],
            'password' => ['sometimes', 'string', 'confirmed', Password::min(8)],
            'disabled' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'string', 'in:lv,en'],
            'date_format' => ['sometimes', 'string', 'max:255'],
            'decimal_separator' => ['sometimes', 'string', 'max:5'],
            'thousand_separator' => ['sometimes', 'string', 'max:5'],
            'table_page_size' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
