<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Roles</h2>
            @canModule('settings', 'add')
                <a href="{{ route('institute.roles.create') }}" class="btn btn-primary btn-sm">+ New Role</a>
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
                        <th>Type</th>
                        <th>Users Assigned</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                @if ($role->is_system)
                                    <span class="badge bg-info text-dark">System</span>
                                @else
                                    <span class="badge bg-secondary">Custom</span>
                                @endif
                            </td>
                            <td>{{ $role->users_count }}</td>
                            <td class="text-end">
                                @if ($role->is_system)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="System roles cannot be edited">Edit</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" disabled title="System roles cannot be deleted">Delete</button>
                                @else
                                    @canModule('settings', 'edit')
                                        <a href="{{ route('institute.roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    @endcanModule
                                    @canModule('settings', 'delete')
                                        <form action="{{ route('institute.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this role? This cannot be undone.');">
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
                            <td colspan="5" class="text-center text-muted py-4">No roles yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-tenant-layout>
