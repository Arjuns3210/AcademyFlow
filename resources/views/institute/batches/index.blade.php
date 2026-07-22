<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
            @canModule('batches', 'add')
                <a href="{{ route('institute.batches.create') }}" class="btn btn-primary btn-sm">+ Add {{ Str::singular($moduleLabel) }}</a>
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

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Teacher</th>
                        <th>Time Slot</th>
                        <th>Enrolled</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td>{{ $batches->firstItem() + $loop->index }}</td>
                            <td>{{ $batch->name }}</td>
                            <td>{{ $batch->course->name }}</td>
                            <td>{{ $batch->teacher->name ?? '— unassigned —' }}</td>
                            <td>{{ $batch->time_slot }}</td>
                            <td>{{ $batch->enrolledCount() }} / {{ $batch->capacity ?? 'Unlimited' }}</td>
                            <td>
                                <span class="badge {{ $batch->isActive() ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @canModule('batches', 'edit')
                                    <a href="{{ route('institute.batches.edit', $batch) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcanModule
                                @canModule('batches', 'status')
                                    @include('institute.batches._status-form', ['batch' => $batch])
                                @endcanModule
                                @canModule('batches', 'delete')
                                    <form action="{{ route('institute.batches.destroy', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this batch?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No batches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $batches->links() }}
    </div>
</x-tenant-layout>
