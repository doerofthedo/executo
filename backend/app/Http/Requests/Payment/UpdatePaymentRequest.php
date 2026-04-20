<?php

declare(strict_types=1);

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePaymentRequest extends FormRequest
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
            'amount' => ['sometimes', 'decimal:0,4', 'min:0.0001'],
            'date' => ['sometimes', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
