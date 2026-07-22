@php $course = $course ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Course Name" :value="$course->name ?? ''" required autofocus />
    </div>
    <div class="col-md-3">
        <x-form-input name="duration" label="Duration" :value="$course->duration ?? ''" required placeholder="e.g. 3 months" />
    </div>
    <div class="col-md-3">
        <x-form-input name="fee" label="Fee (₹)" type="number" step="0.01" min="0" :value="$course->fee ?? ''" required />
    </div>
</div>

<div class="mb-3">
    <x-input-label for="description" value="Description (optional)" />
    <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $course->description ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" />
</div>
