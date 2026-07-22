<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Coupon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Already gated by the 'super_admin' route middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'coupon_code' => ['nullable', 'string'],
            'current_period_start' => ['required', 'date'],
            'current_period_end' => ['required', 'date', 'after:current_period_start'],
        ];
    }

    /**
     * Coupon validity is a cross-field business rule (active, within its
     * date window, under max_uses), not a single-field format check — so it
     * belongs in an after() hook rather than the rules() array.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('coupon_code')) {
                return;
            }

            $coupon = $this->resolvedCoupon();
            $today = now()->toDateString();

            if (! $coupon) {
                $validator->errors()->add('coupon_code', 'This coupon code does not exist.');
            } elseif (! $coupon->is_active) {
                $validator->errors()->add('coupon_code', 'This coupon is not active.');
            } elseif ($today < $coupon->valid_from->toDateString() || $today > $coupon->valid_until->toDateString()) {
                $validator->errors()->add('coupon_code', 'This coupon is outside its valid date range.');
            } elseif ($coupon->max_uses !== null && $coupon->times_used >= $coupon->max_uses) {
                $validator->errors()->add('coupon_code', 'This coupon has reached its maximum number of uses.');
            }
        });
    }

    public function resolvedCoupon(): ?Coupon
    {
        return $this->filled('coupon_code')
            ? Coupon::where('code', $this->input('coupon_code'))->first()
            : null;
    }
}
