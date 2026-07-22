@php
    $role = $role ?? null;
    $actions = ['view', 'add', 'edit', 'delete', 'status'];
@endphp

<x-form-input name="name" label="Role Name" :value="$role->name ?? ''" required autofocus />

<hr class="my-4">

<h3 class="h6 text-uppercase text-muted mb-3">Permissions</h3>

@if ($enabledModules->isEmpty())
    <p class="text-muted">No modules are enabled for your institute yet. Ask your Super Admin to enable at least one module before creating roles.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Module</th>
                    @foreach ($actions as $action)
                        <th class="text-center text-capitalize">
                            {{ $action }}<br>
                            <input type="checkbox" class="select-col" data-col="{{ $action }}" title="Select all {{ $action }}">
                        </th>
                    @endforeach
                    <th class="text-center">All</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($enabledModules as $im)
                    @php
                        $moduleId = $im->module_id;
                        $label = $im->custom_label ?: $im->module->name;
                        $existing = $existingPermissions[$moduleId] ?? null;
                    @endphp
                    <tr>
                        <td>
                            @if ($im->module->icon)
                                <i class="bi {{ $im->module->icon }}"></i>
                            @endif
                            {{ $label }}
                        </td>
                        @foreach ($actions as $action)
                            <td class="text-center">
                                <input
                                    type="checkbox"
                                    class="perm-checkbox"
                                    data-row="{{ $moduleId }}"
                                    data-col="{{ $action }}"
                                    name="permissions[{{ $moduleId }}][{{ $action }}]"
                                    value="1"
                                    {{ old("permissions.$moduleId.$action", $existing?->{"can_$action"} ?? false) ? 'checked' : '' }}
                                >
                            </td>
                        @endforeach
                        <td class="text-center">
                            <input type="checkbox" class="select-row" data-row="{{ $moduleId }}" title="Select all for {{ $label }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<script>
    (function () {
        document.querySelectorAll('.select-col').forEach((colCheckbox) => {
            colCheckbox.addEventListener('change', () => {
                const col = colCheckbox.dataset.col;
                document.querySelectorAll(`.perm-checkbox[data-col="${col}"]`).forEach((cb) => {
                    cb.checked = colCheckbox.checked;
                });
            });
        });

        document.querySelectorAll('.select-row').forEach((rowCheckbox) => {
            rowCheckbox.addEventListener('change', () => {
                const row = rowCheckbox.dataset.row;
                document.querySelectorAll(`.perm-checkbox[data-row="${row}"]`).forEach((cb) => {
                    cb.checked = rowCheckbox.checked;
                });
            });
        });
    })();
</script>
