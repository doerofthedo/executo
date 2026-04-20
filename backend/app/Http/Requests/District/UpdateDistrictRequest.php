<?php

declare(strict_types=1);

namespace App\Http\Requests\District;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDistrictRequest extends FormRequest
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
            'number' => ['sometimes', 'integer', 'min:1', 'max:999'],
            'bailiff_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'bailiff_surname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'court' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'disabled' => ['sometimes', 'boolean'],
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
