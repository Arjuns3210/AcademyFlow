@php $student = $student ?? null; @endphp

@if ($student)
    <div class="mb-3">
        <x-input-label value="Student Code" />
        <input type="text" class="form-control" value="{{ $student->student_code }}" disabled>
        <div class="form-text">Auto-generated on admission — cannot be changed.</div>
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Student Name" :value="$student->name ?? ''" required autofocus />
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="photo" value="Photo (optional, image, max 2MB)" />
            <input type="file" name="photo" id="photo" class="form-control" accept="image/png,image/jpeg,image/webp">
            <x-input-error :messages="$errors->get('photo')" />

            @if ($student?->photo)
                <div class="mt-2">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($student->photo) }}" alt="Current photo" style="height: 48px; width: 48px; object-fit: cover; border-radius: 4px;">
                    <span class="text-muted small">Current photo — uploading a new one will replace it.</span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <x-form-input name="dob" label="Date of Birth" type="date" :value="$student?->dob?->toDateString()" />
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-input-label for="gender" value="Gender" />
            <select name="gender" id="gender" class="form-select">
                <option value="">Select&hellip;</option>
                <option value="male" {{ old('gender', $student->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $student->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                <option value="other" {{ old('gender', $student->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
            </select>
            <x-input-error :messages="$errors->get('gender')" />
        </div>
    </div>
    <div class="col-md-4">
        <x-form-input name="admission_date" label="Admission Date" type="date" :value="($student->admission_date ?? now())->toDateString()" />
    </div>
</div>

<hr class="my-4">
<h3 class="h6 text-uppercase text-muted mb-3">Parent / Guardian</h3>

<div class="row">
    <div class="col-md-4">
        <x-form-input name="parent_name" label="Parent Name" :value="$student->parent_name ?? ''" />
    </div>
    <div class="col-md-4">
        <x-form-input name="parent_mobile" label="Parent Mobile" :value="$student->parent_mobile ?? ''" required placeholder="e.g. 9876543210" />
    </div>
    <div class="col-md-4">
        <x-form-input name="parent_email" label="Parent Email (optional)" type="email" :value="$student->parent_email ?? ''" />
    </div>
</div>

<div class="mb-3">
    <x-input-label for="address" value="Address" />
    <textarea name="address" id="address" class="form-control" rows="2">{{ old('address', $student->address ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('address')" />
</div>

<hr class="my-4">
<h3 class="h6 text-uppercase text-muted mb-3">Course / Batch</h3>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="course_id" value="Course (optional)" />
            <select name="course_id" id="course_id" class="form-select">
                <option value="">&mdash; none &mdash;</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ (int) old('course_id', $student->course_id ?? '') === $course->id ? 'selected' : '' }}>
                        {{ $course->name }} @if (! $course->isActive()) (inactive) @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('course_id')" />
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="batch_id" value="Batch (optional)" />
            <select name="batch_id" id="batch_id" class="form-select">
                <option value="">&mdash; none &mdash;</option>
                @foreach ($batches as $batch)
                    <option
                        value="{{ $batch->id }}"
                        {{ (int) old('batch_id', $student->batch_id ?? '') === $batch->id ? 'selected' : '' }}
                        {{ $batch->isFull($student?->id) ? 'disabled' : '' }}
                    >
                        {{ $batch->name }} ({{ $batch->enrolledCount($student?->id) }}/{{ $batch->capacity ?? 'Unlimited' }}){{ $batch->isFull($student?->id) ? ' — FULL' : '' }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">Pick a course first to see its batches.</div>
            <x-input-error :messages="$errors->get('batch_id')" />
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var courseSelect = document.getElementById('course_id');
        var batchSelect = document.getElementById('batch_id');
        var urlTemplate = @json(route('institute.students.course-batches', ['course' => '__COURSE_ID__']));
        var currentBatchId = @json($student->batch_id ?? null);

        courseSelect.addEventListener('change', function () {
            var courseId = courseSelect.value;

            batchSelect.innerHTML = '';
            batchSelect.appendChild(new Option('— none —', ''));

            if (! courseId) {
                return;
            }

            fetch(urlTemplate.replace('__COURSE_ID__', courseId), { headers: { Accept: 'application/json' } })
                .then(function (response) { return response.json(); })
                .then(function (batches) {
                    batches.forEach(function (batch) {
                        var label = batch.name + ' (' + batch.enrolled + '/' + (batch.capacity ?? 'Unlimited') + ')' + (batch.is_full ? ' — FULL' : '');
                        var option = new Option(label, batch.id, false, batch.id === currentBatchId);
                        option.disabled = batch.is_full;
                        batchSelect.appendChild(option);
                    });
                });
        });
    });
</script>
