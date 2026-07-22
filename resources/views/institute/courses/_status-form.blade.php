<form action="{{ route('institute.courses.update-status', $course) }}" method="POST" class="d-flex gap-1">
    @csrf
    @method('PATCH')
    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.requestSubmit()">
        <option value="active" {{ $course->status === 'active' ? 'selected' : '' }}>Active</option>
        <option value="inactive" {{ $course->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
    </select>
</form>
