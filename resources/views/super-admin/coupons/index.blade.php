<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Coupons</h2>
            <a href="{{ route('super-admin.coupons.create') }}" class="btn btn-primary btn-sm">+ New Coupon</a>
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
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Valid Period</th>
                        <th>Uses</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><code>{{ $coupon->code }}</code></td>
                            <td>
                                {{ $coupon->discount_type === 'percentage' ? number_format($coupon->discount_value, 2).'%' : '₹'.number_format($coupon->discount_value, 2) }}
                            </td>
                            <td>{{ $coupon->valid_from->format('d M Y') }} &ndash; {{ $coupon->valid_until->format('d M Y') }}</td>
                            <td>{{ $coupon->times_used }} / {{ $coupon->max_uses ?? '∞' }}</td>
                            <td>
                                <form action="{{ route('super-admin.coupons.toggle-status', $coupon) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $coupon->is_active ? 'btn-success' : 'btn-secondary' }}">
                                        {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('super-admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form action="{{ route('super-admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No coupons yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-super-admin-layout>
