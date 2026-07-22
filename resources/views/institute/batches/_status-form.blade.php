<form action="{{ route('institute.batches.update-status', $batch) }}" method="POST" class="d-flex gap-1">
    @csrf
    @method('PATCH')
    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.requestSubmit()">
        <option value="active" {{ $batch->status === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $batch->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
</form>
