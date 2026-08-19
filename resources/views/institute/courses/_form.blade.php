@php
    $course = $course ?? null;
    $durationParts = $course?->durationParts() ?? ['value' => '', 'unit' => ''];
@endphp

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Course Name" :value="$course->name ?? ''" required autofocus />
    </div>
    <div class="col-md-2">
        <x-form-input name="duration_value" label="Duration" type="number" min="1" :value="$durationParts['value']" required />
    </div>
    <div class="col-md-2">
        <div class="mb-3">
            <x-input-label for="duration_unit" value="Unit" />
            <select name="duration_unit" id="duration_unit" class="form-select" required>
                <option value="">Select&hellip;</option>
                @foreach (['Weeks', 'Months', 'Years'] as $unit)
                    <option value="{{ $unit }}" {{ old('duration_unit', $durationParts['unit']) === $unit ? 'selected' : '' }}>{{ $unit }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('duration_unit')" />
        </div>
    </div>
    <div class="col-md-2">
        <x-form-input name="fee" label="Fee (₹)" type="number" step="0.01" min="0" :value="$course->fee ?? ''" required />
    </div>
</div>

<div class="mb-3">
    <x-input-label for="description" value="Description (optional)" />
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $course->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
</div>
