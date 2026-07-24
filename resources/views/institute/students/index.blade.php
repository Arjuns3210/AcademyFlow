<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $showTrashed ? 'Students — Trash' : 'Students' }}</h2>
            @canModule('students', 'add')
                <a href="{{ route('institute.students.create') }}" class="btn btn-primary btn-sm">+ Admit Student</a>
            @endcanModule
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.students.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, code, student or parent mobile" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="dropped" {{ $statusFilter === 'dropped' ? 'selected' : '' }}>Dropped</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Course</label>
                    <select name="course_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" {{ $courseFilter === (string) $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Batch</label>
                    <select name="batch_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $batchFilter === (string) $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary flex-grow-1">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">
            {{ $students->total() }} {{ Str::plural('student', $students->total()) }}
            @if ($showTrashed) in trash @endif
        </div>
        <div>
            @if ($showTrashed)
                <a href="{{ route('institute.students.index') }}" class="small">&larr; Back to active students</a>
            @else
                <a href="{{ route('institute.students.index', ['trashed' => 1]) }}" class="small text-muted">View Trash</a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th></th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Student Mobile</th>
                        <th>Parent Mobile</th>
                        <th>Status</th>
                        <th>Admitted</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $students->firstItem() + $loop->index }}</td>
                            <td>
                                @if ($student->photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($student->photo) }}" alt="" style="height: 32px; width: 32px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 32px; width: 32px; border-radius: 4px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                @endif
                            </td>
                            <td><code>{{ $student->student_code }}</code></td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->student_mobile ?: '—' }}</td>
                            <td>{{ $student->parent_mobile }}</td>
                            <td>
                                <span class="badge {{ match($student->status) { 'active' => 'bg-success', 'inactive' => 'bg-secondary', 'dropped' => 'bg-danger' } }}">
                                    {{ ucfirst($student->status) }}
                                </span>
                            </td>
                            <td>{{ $student->admission_date->format('d M Y') }}</td>
                            <td class="text-end">
                                @if ($showTrashed)
                                    <span class="text-muted small">Trashed</span>
                                @else
                                    <a href="{{ route('institute.students.show', $student) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @canModule('students', 'edit')
                                        <a href="{{ route('institute.students.edit', $student) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @endcanModule
                                    @canModule('students', 'status')
                                        @include('institute.students._status-form', ['student' => $student])
                                    @endcanModule
                                    @canModule('students', 'delete')
                                        <form action="{{ route('institute.students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Move this student to trash?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endcanModule
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                {{ $showTrashed ? 'No students in trash.' : 'No students yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $students->links() }}
    </div>
</x-tenant-layout>
