<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Plans</h2>
            <a href="{{ route('super-admin.plans.create') }}" class="btn btn-primary btn-sm">+ New Plan</a>
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
                        <th class="text-end">Price</th>
                        <th>Billing Cycle</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                {{ $plan->name }}
                                @if ($plan->description)
                                    <div class="text-muted small">{{ $plan->description }}</div>
                                @endif
                            </td>
                            <td class="text-end">₹{{ number_format($plan->price, 2) }}</td>
                            <td>{{ ucfirst($plan->billing_cycle) }}</td>
                            <td>
                                <form action="{{ route('super-admin.plans.toggle-status', $plan) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('super-admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form action="{{ route('super-admin.plans.destroy', $plan) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this plan? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No plans yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-super-admin-layout>
