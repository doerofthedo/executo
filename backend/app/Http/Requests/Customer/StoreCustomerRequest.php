<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerRequest extends FormRequest
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
            'case_number' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['physical', 'legal'])],
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255', 'required_if:type,physical'],
            'last_name' => ['nullable', 'string', 'max:255', 'required_if:type,physical'],
            'personal_code' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:type,legal'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
        ];
    }
}
