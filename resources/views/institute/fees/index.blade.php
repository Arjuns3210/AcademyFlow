<x-tenant-layout>
    <x-slot name="header">
        <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.fees.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Student name or code" value="{{ $search }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
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
                        <th>Student</th>
                        <th class="text-end">Total Fee</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Net Fee</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Pending</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fees as $fee)
                        <tr>
                            <td>{{ $fees->firstItem() + $loop->index }}</td>
                            <td>
                                {{ $fee->student->name }}
                                <div class="text-muted small"><code>{{ $fee->student->student_code }}</code></div>
                            </td>
                            <td class="text-end">₹{{ number_format($fee->total_fee, 2) }}</td>
                            <td class="text-end">₹{{ number_format($fee->discount_amount, 2) }}</td>
                            <td class="text-end">₹{{ number_format($fee->net_fee, 2) }}</td>
                            <td class="text-end">₹{{ number_format($fee->paid_amount, 2) }}</td>
                            <td class="text-end {{ $fee->pending_amount > 0 ? 'text-danger fw-semibold' : '' }}">
                                ₹{{ number_format($fee->pending_amount, 2) }}
                            </td>
                            <td>
                                <span class="badge {{ match($fee->status) { 'paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'pending' => 'bg-danger' } }}">
                                    {{ ucfirst($fee->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('institute.fees.show', $fee) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No fee records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $fees->links() }}
    </div>
</x-tenant-layout>
