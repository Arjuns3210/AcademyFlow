<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountValueRules = ['required', 'numeric', 'min:0'];

        if ($this->input('discount_type') === 'percentage') {
            $discountValueRules[] = 'max:100';
        }

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($this->route('coupon'))],
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
