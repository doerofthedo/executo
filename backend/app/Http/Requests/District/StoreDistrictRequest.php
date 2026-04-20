<?php

declare(strict_types=1);

namespace App\Http\Requests\District;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:999'],
            'bailiff_name' => ['nullable', 'string', 'max:255'],
            'bailiff_surname' => ['nullable', 'string', 'max:255'],
            'court' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'disabled' => ['sometimes', 'boolean'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
