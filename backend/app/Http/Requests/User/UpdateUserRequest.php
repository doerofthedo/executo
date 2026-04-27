<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, In|Password|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'surname' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email:rfc', 'max:255'],
            'password' => ['sometimes', 'string', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'disabled' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'string', 'in:lv,en'],
            'timezone' => ['sometimes', 'string', Rule::in([
                'Europe/Riga',
                'UTC',
            ])],
            'default_district_ulid' => ['sometimes', 'nullable', 'string', 'exists:districts,ulid'],
            'date_format' => ['sometimes', 'string', Rule::in([
                'DD.MM.YYYY.',
                'DD.MM.YYYY',
                'DD-MM-YYYY',
                'DD-MMM-YYYY',
                'YYYY-MM-DD',
            ])],
            'decimal_separator' => ['sometimes', 'string', Rule::in([
                '.',
                ',',
            ])],
            'thousand_separator' => ['sometimes', 'string', Rule::in([
                ' ',
                '.',
                ',',
                "'",
            ])],
            'table_page_size' => ['sometimes', 'integer', Rule::in([
                10,
                25,
                50,
                100,
            ])],
        ];
    }
}
