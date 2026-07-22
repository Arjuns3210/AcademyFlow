<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'permission' route middleware.
        return true;
    }

    public function rules(): array
    {
        $studentFee = $this->route('studentFee');

        return [
            'discount_amount' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($studentFee) {
                    if ((float) $value > (float) $studentFee->total_fee) {
                        $fail(sprintf('Discount cannot exceed the total fee of ₹%s.', number_format($studentFee->total_fee, 2)));
                    }
                },
            ],
            'discount_reason' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ((float) $this->input('discount_amount') > 0 && ! $value) {
                        $fail('Please provide a reason for the discount.');
                    }
                },
            ],
        ];
    }
}
