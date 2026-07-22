<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'super_admin' route middleware.
        return true;
    }

    public function rules(): array
    {
        $discountValueRules = ['required', 'numeric', 'min:0'];

        // A percentage discount over 100 makes no sense; flat discounts have
        // no such ceiling (a flat amount is just rupees, however large).
        if ($this->input('discount_type') === 'percentage') {
            $discountValueRules[] = 'max:100';
        }

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')],
            'discount_type' => ['required', Rule::in(['flat', 'percentage'])],
            'discount_value' => $discountValueRules,
            'valid_from' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after_or_equal:valid_from'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
