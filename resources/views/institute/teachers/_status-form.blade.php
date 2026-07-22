<form action="{{ route('institute.teachers.update-status', $teacher) }}" method="POST" class="d-flex gap-1">
    @csrf
    @method('PATCH')
    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.requestSubmit()">
        <option value="active" {{ $teacher->status === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $teacher->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
</form>
