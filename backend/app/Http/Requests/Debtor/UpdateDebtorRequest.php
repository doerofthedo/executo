<?php

declare(strict_types=1);

namespace App\Http\Requests\Debtor;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rule;

final class UpdateDebtorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|In|string>>
     */
    public function rules(): array
    {
        return [
            'restore' => ['sometimes', 'boolean'],
            'case_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(['physical', 'legal'])],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'personal_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
