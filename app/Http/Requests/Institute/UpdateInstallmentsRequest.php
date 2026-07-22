<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'permission' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'installments_allowed' => ['required', 'boolean'],
            'installment_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
