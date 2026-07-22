<?php

namespace App\Http\Requests\Institute;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) use ($studentFee) {
                    if ((float) $value > $studentFee->pending_amount) {
                        $fail(sprintf('Amount cannot exceed the pending balance of ₹%s.', number_format($studentFee->pending_amount, 2)));
                    }
                },
            ],
            'payment_mode' => ['required', 'in:cash,upi,card,bank_transfer,cheque,other'],
            'payment_date' => ['nullable', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}
