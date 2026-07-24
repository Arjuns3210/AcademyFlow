@php
    $plan = $plan ?? null;
    $staffLimitUnlimited = old('staff_limit_unlimited', $plan && $plan->staff_limit === null);
@endphp

<x-form-input name="name" label="Plan Name" :value="$plan->name ?? ''" required autofocus />
<x-form-input name="price" label="Price (₹)" type="number" step="0.01" min="0" :value="$plan->price ?? ''" required />

<div class="mb-3">
    <x-input-label for="billing_cycle" value="Billing Cycle" />
    <select name="billing_cycle" id="billing_cycle" class="form-select" required>
        @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $value => $label)
            <option value="{{ $value }}" {{ old('billing_cycle', $plan->billing_cycle ?? 'monthly') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('billing_cycle')" />
</div>

<div class="mb-3">
    <x-input-label for="staff_limit" value="Staff Limit" />
    <div class="d-flex align-items-center gap-2">
        <x-text-input id="staff_limit" name="staff_limit" type="number" min="1" step="1" class="w-auto"
            style="max-width: 120px;"
            :value="old('staff_limit', $plan->staff_limit ?? '')"
            :disabled="$staffLimitUnlimited" />
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" id="staff_limit_unlimited" name="staff_limit_unlimited" value="1"
                {{ $staffLimitUnlimited ? 'checked' : '' }}
                onchange="document.getElementById('staff_limit').disabled = this.checked;">
            <label class="form-check-label" for="staff_limit_unlimited">Unlimited</label>
        </div>
    </div>
    <div class="form-text">How many staff members (including the institute admin) an institute on this plan may have.</div>
    <x-input-error :messages="$errors->get('staff_limit')" />
</div>

<div class="mb-3">
    <x-input-label for="description" value="Description (optional)" />
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $plan->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
        {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
