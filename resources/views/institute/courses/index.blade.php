<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
            @canModule('courses', 'add')
                <a href="{{ route('institute.courses.create') }}" class="btn btn-primary btn-sm">+ Add {{ Str::singular($moduleLabel) }}</a>
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
            <form action="{{ route('institute.courses.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Course name" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Name</th>
                        <th>Duration</th>
                        <th>Fee</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td>{{ $courses->firstItem() + $loop->index }}</td>
                            <td>{{ $course->name }}</td>
                            <td>{{ $course->duration }}</td>
                            <td>&#8377;{{ number_format($course->fee, 2) }}</td>
                            <td>
                                <span class="badge {{ $course->isActive() ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($course->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @canModule('courses', 'edit')
                                    <a href="{{ route('institute.courses.edit', $course) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcanModule
                                @canModule('courses', 'status')
                                    @include('institute.courses._status-form', ['course' => $course])
                                @endcanModule
                                @canModule('courses', 'delete')
                                    <form action="{{ route('institute.courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this course?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No courses yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $courses->links() }}
    </div>
</x-tenant-layout>
