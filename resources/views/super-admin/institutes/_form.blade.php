@php
    $institute = $institute ?? null;
    $selectedModuleIds = collect(old('modules', $selectedModuleIds ?? []))->map(fn ($id) => (int) $id)->all();
    $customLabels = $customLabels ?? [];
@endphp

<h3 class="h6 text-uppercase text-muted mb-3">Institute Details</h3>

<div class="row">
    <div class="col-md-6">
        <x-form-input name="name" label="Institute Name" :value="$institute->name ?? ''" required autofocus />
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="institute_type_id" value="Institute Type" />
            <select name="institute_type_id" id="institute_type_id" class="form-select" required>
                <option value="">Select a type&hellip;</option>
                @foreach ($instituteTypes as $type)
                    <option value="{{ $type->id }}" {{ (int) old('institute_type_id', $institute->institute_type_id ?? '') === $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('institute_type_id')" />
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <x-input-label for="logo" value="Logo (optional, image, max 2MB)" />
            <input type="file" name="logo" id="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
            <x-input-error :messages="$errors->get('logo')" />

            @if ($institute?->logo)
                <div class="mt-2">
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($institute->logo) }}" alt="Current logo" style="height: 48px;">
                    <span class="text-muted small">Current logo — uploading a new one will replace it.</span>
                </div>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <x-form-input name="contact" label="Contact Number" :value="$institute->contact ?? ''" />
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <x-form-input name="email" label="Institute Email" type="email" :value="$institute->email ?? ''" />
    </div>
    <div class="col-md-6">
        <x-form-input name="gst_number" label="GST Number (optional)" :value="$institute->gst_number ?? ''" />
    </div>
</div>

@php
    $selectedThemeColor = old('theme_color', $institute->theme_color ?? '#212529');
    $presetColors = \App\Support\ThemeColorPresets::colors();
    // An institute already on a color from before presets existed still
    // needs a swatch to resubmit unchanged — otherwise saving any other
    // field on this form would silently reset it to the default, since no
    // preset would ever show as selected.
    $legacyColor = ($institute?->theme_color && ! in_array($institute->theme_color, $presetColors, true))
        ? $institute->theme_color
        : null;
@endphp

<div class="mb-3">
    <x-input-label value="Sidebar Theme" />
    <div class="d-flex flex-wrap gap-2">
        @if ($legacyColor)
            <label class="theme-swatch-label" title="Current color">
                <input
                    type="radio"
                    name="theme_color"
                    value="{{ $legacyColor }}"
                    class="theme-swatch-input"
                    {{ $selectedThemeColor === $legacyColor ? 'checked' : '' }}
                >
                <span class="theme-swatch" style="background-color: {{ $legacyColor }};"></span>
            </label>
        @endif
        @foreach (\App\Support\ThemeColorPresets::all() as $preset)
            <label class="theme-swatch-label" title="{{ $preset['label'] }}">
                <input
                    type="radio"
                    name="theme_color"
                    value="{{ $preset['color'] }}"
                    class="theme-swatch-input"
                    {{ $selectedThemeColor === $preset['color'] ? 'checked' : '' }}
                >
                <span class="theme-swatch" style="background-color: {{ $preset['color'] }};"></span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('theme_color')" />
    <div class="form-text">
        @if ($legacyColor)
            This institute is using a custom color set before presets existed (shown first) — pick a standard option to switch, or leave it as-is.
        @else
            Each option is pre-checked for readable sidebar text — pick one to customize it for this institute.
        @endif
    </div>
</div>

<div class="mb-3">
    <x-input-label for="address" value="Address" />
    <textarea name="address" id="address" class="form-control" rows="2">{{ old('address', $institute->address ?? '') }}</textarea>
    <x-input-error :messages="$errors->get('address')" />
</div>

<hr class="my-4">

<h3 class="h6 text-uppercase text-muted mb-3">Modules</h3>
<p class="text-muted small">
    Checking a module enables it for this institute. Unchecking a module hides it from institute staff but never deletes any data already stored under it.
    Custom label lets you rename a module for this institute only (e.g. "Teachers" &rarr; "Dance Masters").
</p>

<div class="row">
    @foreach ($modules as $module)
        <div class="col-md-6 mb-2">
            <div class="d-flex align-items-center gap-2 border rounded p-2">
                <div class="form-check mb-0" style="min-width: 160px;">
                    <input
                        class="form-check-input module-checkbox"
                        type="checkbox"
                        name="modules[]"
                        value="{{ $module->id }}"
                        id="module-{{ $module->id }}"
                        data-module-id="{{ $module->id }}"
                        {{ in_array($module->id, $selectedModuleIds, true) ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="module-{{ $module->id }}">
                        @if ($module->icon)
                            <i class="bi {{ $module->icon }}"></i>
                        @endif
                        {{ $module->name }}
                    </label>
                </div>
                <input
                    type="text"
                    class="form-control form-control-sm custom-label-input"
                    name="custom_labels[{{ $module->id }}]"
                    data-for="{{ $module->id }}"
                    placeholder="{{ $module->name }}"
                    value="{{ old('custom_labels.'.$module->id, $customLabels[$module->id] ?? '') }}"
                    {{ in_array($module->id, $selectedModuleIds, true) ? '' : 'disabled' }}
                >
            </div>
        </div>
    @endforeach
</div>
<x-input-error :messages="$errors->get('modules')" />

<script>
    (function () {
        const typeModuleDefaults = @json($typeModuleDefaults);

        function toggleLabelInput(moduleId, checked) {
            const input = document.querySelector(`.custom-label-input[data-for="${moduleId}"]`);
            if (input) {
                input.disabled = !checked;
                if (!checked) {
                    input.value = '';
                }
            }
        }

        document.querySelectorAll('.module-checkbox').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                toggleLabelInput(checkbox.dataset.moduleId, checkbox.checked);
            });
        });

        const typeSelect = document.getElementById('institute_type_id');
        if (typeSelect) {
            typeSelect.addEventListener('change', () => {
                const defaults = (typeModuleDefaults[typeSelect.value] || []).map(Number);

                document.querySelectorAll('.module-checkbox').forEach((checkbox) => {
                    const moduleId = parseInt(checkbox.dataset.moduleId, 10);
                    checkbox.checked = defaults.includes(moduleId);
                    toggleLabelInput(moduleId, checkbox.checked);
                });
            });
        }
    })();
</script>
