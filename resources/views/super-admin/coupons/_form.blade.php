@php $coupon = $coupon ?? null; @endphp

@if ($coupon)
    <p class="text-muted small">Used {{ $coupon->times_used }} time{{ $coupon->times_used === 1 ? '' : 's' }} so far.</p>
@endif

<x-form-input name="code" label="Coupon Code" :value="$coupon->code ?? ''" required autofocus />

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="discount_type" value="Discount Type" />
            <select name="discount_type" id="discount_type" class="form-select" required>
                @foreach (['flat' => 'Flat (₹)', 'percentage' => 'Percentage (%)'] as $value => $label)
                    <option value="{{ $value }}" {{ old('discount_type', $coupon->discount_type ?? 'flat') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('discount_type')" />
        </div>
    </div>
    <div class="col-md-6">
        <x-form-input name="discount_value" label="Discount Value" type="number" step="0.01" min="0" :value="$coupon->discount_value ?? ''" required />
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <x-form-input name="valid_from" label="Valid From" type="date" :value="$coupon?->valid_from?->toDateString() ?? ''" min="1900-01-01" required />
    </div>
    <div class="col-md-6">
        <x-form-input name="valid_until" label="Valid Until" type="date" :value="$coupon?->valid_until?->toDateString() ?? ''" min="1900-01-01" required />
    </div>
</div>

<x-form-input name="max_uses" label="Max Uses (leave blank for unlimited)" type="number" min="1" :value="$coupon->max_uses ?? ''" />

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
        {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
