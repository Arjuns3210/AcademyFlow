@php $batch = $batch ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Batch Name" :value="$batch->name ?? ''" required autofocus />
    </div>
    <div class="col-md-6">
        <x-form-input name="time_slot" label="Time Slot" :value="$batch->time_slot ?? ''" required placeholder="e.g. 4:00 PM - 5:30 PM" />
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <x-input-label for="course_id" value="Course" />
            <select name="course_id" id="course_id" class="form-select" required>
                <option value="">Select&hellip;</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ (int) old('course_id', $batch->course_id ?? '') === $course->id ? 'selected' : '' }}>
                        {{ $course->name }} @if (! $course->isActive()) (inactive) @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('course_id')" />
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <x-input-label for="teacher_id" value="Teacher (optional)" />
            <select name="teacher_id" id="teacher_id" class="form-select">
                <option value="">&mdash; unassigned &mdash;</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ (int) old('teacher_id', $batch->teacher_id ?? '') === $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }} @if (! $teacher->isActive()) (inactive) @endif
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('teacher_id')" />
        </div>
    </div>
    <div class="col-md-4">
        <x-form-input name="capacity" label="Capacity (optional)" type="number" min="1" :value="$batch->capacity ?? ''" placeholder="Leave blank for unlimited" />
    </div>
</div>
