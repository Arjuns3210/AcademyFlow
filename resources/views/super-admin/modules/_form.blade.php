@php $module = $module ?? null; @endphp

<x-form-input name="name" label="Name" :value="$module->name ?? ''" required autofocus />
<x-form-input name="slug" label="Slug" :value="$module->slug ?? ''" required />
<x-form-input name="icon" label="Icon (Bootstrap Icons class, optional)" :value="$module->icon ?? ''" placeholder="e.g. bi-people" />

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1"
        {{ old('is_active', $module->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Active</label>
</div>

<p class="text-muted small">
    Deactivating a module hides it from being newly enabled for institutes — it does not remove it from institutes that already have it enabled, nor delete any data already stored under it.
</p>

<script>
    (function () {
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        let slugManuallyEdited = {{ $module ? 'true' : 'false' }};

        slugInput.addEventListener('input', () => {
            slugManuallyEdited = true;
        });

        nameInput.addEventListener('input', () => {
            if (slugManuallyEdited) {
                return;
            }

            slugInput.value = nameInput.value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
        });
    })();
</script>
