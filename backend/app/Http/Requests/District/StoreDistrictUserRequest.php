<?php

declare(strict_types=1);

namespace App\Http\Requests\District;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rule;

final class StoreDistrictUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, In|string>>
     */
    public function rules(): array
    {
        return $this->roleRules(withEmail: true);
    }

    /**
     * @return array<string, array<int, In|string>>
     */
    protected function roleRules(bool $withEmail): array
    {
        $rules = [
            'role' => ['required', 'string', Rule::in([
                'district.admin',
                'district.manager',
                'district.user',
            ])],
        ];

        if ($withEmail) {
            $rules['email'] = ['required', 'string', 'email:rfc', 'max:255'];
        }

        return $rules;
    }
}
