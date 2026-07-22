<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Staff</h2>
            @canModule('settings', 'add')
                <a href="{{ route('institute.staff.create') }}" class="btn btn-primary btn-sm">+ New Staff Member</a>
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
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $member)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                {{ $member->name }}
                                @if ($member->id === auth()->id())
                                    <span class="badge bg-light text-dark border">You</span>
                                @endif
                            </td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->roleRecord?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $member->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $member->is_active ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                            <td class="text-end">
                                @canModule('settings', 'edit')
                                    <a href="{{ route('institute.staff.edit', $member) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcanModule
                                @canModule('settings', 'status')
                                    @if ($member->id !== auth()->id())
                                        <form action="{{ route('institute.staff.toggle-status', $member) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ $member->is_active ? 'Deactivate' : 'Activate' }} this staff member?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $member->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No staff yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-tenant-layout>
