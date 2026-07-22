@php $plan = $plan ?? null; @endphp

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
    <x-input-label for="description" value="Description (optional)" />
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $plan->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
        {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>
