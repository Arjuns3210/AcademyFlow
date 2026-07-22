<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
            @canModule('teachers', 'add')
                <a href="{{ route('institute.teachers.create') }}" class="btn btn-primary btn-sm">+ Add {{ Str::singular($moduleLabel) }}</a>
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
            <form action="{{ route('institute.teachers.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name or mobile" value="{{ $search }}">
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
                        <th></th>
                        <th>Name</th>
                        <th>Qualification</th>
                        <th>Mobile</th>
                        <th>Subjects</th>
                        <th>Login</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $teacher)
                        <tr>
                            <td>{{ $teachers->firstItem() + $loop->index }}</td>
                            <td>
                                @if ($teacher->photo)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($teacher->photo) }}" alt="" style="height: 32px; width: 32px; object-fit: cover; border-radius: 4px;">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="height: 32px; width: 32px; border-radius: 4px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $teacher->name }}</td>
                            <td>{{ $teacher->qualification ?: '—' }}</td>
                            <td>{{ $teacher->mobile }}</td>
                            <td>{{ $teacher->subjects ?: '—' }}</td>
                            <td>{{ $teacher->user?->email ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $teacher->isActive() ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($teacher->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @canModule('teachers', 'edit')
                                    <a href="{{ route('institute.teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcanModule
                                @canModule('teachers', 'status')
                                    @include('institute.teachers._status-form', ['teacher' => $teacher])
                                @endcanModule
                                @canModule('teachers', 'delete')
                                    <form action="{{ route('institute.teachers.destroy', $teacher) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this teacher?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No teachers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $teachers->links() }}
    </div>
</x-tenant-layout>
