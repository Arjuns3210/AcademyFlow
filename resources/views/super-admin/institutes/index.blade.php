<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Institutes</h2>
            <a href="{{ route('super-admin.institutes.create') }}" class="btn btn-primary btn-sm">+ New Institute</a>
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
                        <th>Type</th>
                        <th>Status</th>
                        <th>Modules</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($institutes as $institute)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $institute->name }}</td>
                            <td>{{ $institute->instituteType?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $institute->isActive() ? 'bg-success' : 'bg-danger' }}">
                                    {{ $institute->isActive() ? 'Active' : 'Blocked' }}
                                </span>
                            </td>
                            <td>{{ $institute->enabled_modules_count }}</td>
                            <td>{{ $institute->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('super-admin.institutes.show', $institute) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                <a href="{{ route('super-admin.institutes.edit', $institute) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form action="{{ route('super-admin.institutes.toggle-status', $institute) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $institute->isActive() ? 'Block' : 'Unblock' }} this institute?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $institute->isActive() ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $institute->isActive() ? 'Block' : 'Unblock' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No institutes yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-super-admin-layout>
