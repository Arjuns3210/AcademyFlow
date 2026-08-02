<x-super-admin-layout>
    <x-slot name="header">
        <h2 class="h4 mb-0">Subscriptions</h2>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Institute</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Trial Ends / Period Ends</th>
                        <th class="text-end">Amount Paid</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscriptions as $subscription)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $subscription->institute->name }}</td>
                            <td>{{ $subscription->plan?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ match($subscription->status) { 'trial' => 'bg-warning text-dark', 'active' => 'bg-success', 'expired' => 'bg-danger', 'cancelled' => 'bg-secondary' } }}">
                                    {{ ucfirst($subscription->status) }}
                                </span>
                            </td>
                            @php
                                $endDate = $subscription->status === 'trial' ? $subscription->trial_ends_at : $subscription->current_period_end;
                                $daysDue = $endDate ? now()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false) : null;
                            @endphp
                            <td>
                                @if ($endDate)
                                    {{ $endDate->format('d M Y') }}
                                    @if ($daysDue !== null && $daysDue >= 0 && $daysDue <= 30)
                                        <div class="text-muted small">Due in {{ $daysDue }} day{{ $daysDue === 1 ? '' : 's' }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ $subscription->amount_paid !== null ? '₹'.number_format($subscription->amount_paid, 2) : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('super-admin.subscriptions.edit', $subscription) }}" class="btn btn-sm btn-outline-primary">Activate / Renew</a>
                                <form action="{{ route('super-admin.subscriptions.cancel', $subscription) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel the subscription for {{ $subscription->institute->name }}?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
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
