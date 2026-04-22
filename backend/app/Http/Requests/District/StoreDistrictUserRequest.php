<?php

declare(strict_types=1);

namespace App\Http\Requests\District;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDistrictUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'role' => ['required', 'string', Rule::in([
                'district.admin',
                'district.manager',
                'district.user',
            ])],
        ];
    }
}
