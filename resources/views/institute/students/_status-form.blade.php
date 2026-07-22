<form action="{{ route('institute.students.update-status', $student) }}" method="POST" class="d-flex gap-1">
    @csrf
    @method('PATCH')
    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.requestSubmit()">
        <option value="active" {{ $student->status === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $student->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
        <option value="dropped" {{ $student->status === 'dropped' ? 'selected' : '' }}>Dropped</option>
    </select>
</form>
