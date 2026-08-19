@php $staffMember = $staffMember ?? null; @endphp

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Name" :value="$staffMember->name ?? ''" required autofocus />
    </div>
    <div class="col-md-6">
        <x-form-input name="email" label="Email" type="email" :value="$staffMember->email ?? ''" required />
    </div>
</div>

<div class="mb-3">
    <x-input-label for="role_id" value="Role" required />
    <select name="role_id" id="role_id" class="form-select" required>
        <option value="">Select a role&hellip;</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" {{ (int) old('role_id', $staffMember->role_id ?? '') === $role->id ? 'selected' : '' }}>
                {{ $role->name }}{{ $role->is_system ? ' (System)' : '' }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role_id')" />
</div>

@unless ($staffMember)
    <div class="row">
        <div class="col-md-6">
            <x-form-input name="password" label="Password" type="password" required />
        </div>
        <div class="col-md-6">
            <x-form-input name="password_confirmation" label="Confirm Password" type="password" required />
        </div>
    </div>
@endunless
