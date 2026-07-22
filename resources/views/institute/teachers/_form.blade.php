@php $teacher = $teacher ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Teacher Name" :value="$teacher->name ?? ''" required autofocus />
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="photo" value="Photo (optional, image, max 2MB)" />
            <input type="file" name="photo" id="photo" class="form-control" accept="image/png,image/jpeg,image/webp">
            <x-input-error :messages="$errors->get('photo')" />

            @if ($teacher?->photo)
                <div class="mt-2">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($teacher->photo) }}" alt="Current photo" style="height: 48px; width: 48px; object-fit: cover; border-radius: 4px;">
                    <span class="text-muted small">Current photo — uploading a new one will replace it.</span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <x-form-input name="qualification" label="Qualification (optional)" :value="$teacher->qualification ?? ''" />
    </div>
    <div class="col-md-4">
        <x-form-input name="mobile" label="Mobile" :value="$teacher->mobile ?? ''" required placeholder="e.g. 9876543210" />
    </div>
    <div class="col-md-4">
        <x-form-input name="email" label="Email (optional)" type="email" :value="$teacher->email ?? ''" />
    </div>
</div>

<div class="mb-3">
    <x-form-input name="subjects" label="Subjects (optional, comma-separated)" :value="$teacher->subjects ?? ''" placeholder="e.g. Maths, Physics" />
</div>

<div class="mb-3">
    <x-input-label for="user_id" value="Link to Staff Login (optional)" />
    <select name="user_id" id="user_id" class="form-select">
        <option value="">&mdash; none &mdash;</option>
        @foreach ($linkableUsers as $user)
            <option value="{{ $user->id }}" {{ (int) old('user_id', $teacher->user_id ?? '') === $user->id ? 'selected' : '' }}>
                {{ $user->name }} ({{ $user->email }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Only needed if this teacher should also be able to log in (e.g. to mark attendance).</div>
    <x-input-error :messages="$errors->get('user_id')" />
</div>
