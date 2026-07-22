@php
    $instituteType = $instituteType ?? null;
    $selectedModuleIds = collect(old('modules', $selectedModuleIds ?? []))->map(fn ($id) => (int) $id)->all();
@endphp

<x-form-input name="name" label="Name" :value="$instituteType->name ?? ''" required autofocus />

<div class="mb-3">
    <label class="form-label">Default Modules</label>
    <div class="form-text mb-2">
        Pre-selected modules for institutes created with this type — Super Admin can still add/remove modules individually per institute afterward.
    </div>

    @if ($modules->isEmpty())
        <p class="text-muted">No active modules available. Create one first.</p>
    @else
        <div class="row">
            @foreach ($modules as $module)
                <div class="col-md-6">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="modules[]"
                            value="{{ $module->id }}"
                            id="module-{{ $module->id }}"
                            {{ in_array($module->id, $selectedModuleIds, true) ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="module-{{ $module->id }}">
                            @if ($module->icon)
                                <i class="bi {{ $module->icon }}"></i>
                            @endif
                            {{ $module->name }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-input-error :messages="$errors->get('modules')" />
</div>
