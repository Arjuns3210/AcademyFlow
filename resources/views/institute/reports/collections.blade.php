<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Collection Report</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('institute.reports.collections.export', $filters) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                <a href="{{ route('institute.reports.collections.export-pdf', $filters) }}" class="btn btn-outline-secondary btn-sm">Export PDF</a>
                <x-back-link :href="route('institute.reports.index')" />
            </div>
        </div>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.reports.collections') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}" min="1900-01-01" max="{{ now()->toDateString() }}" data-range-group="range" data-range-role="from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}" min="1900-01-01" max="{{ now()->toDateString() }}" data-range-group="range" data-range-role="to">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Payment Mode</label>
                    <select name="payment_mode" class="form-select">
                        <option value="">All</option>
                        <option value="cash" {{ $filters['payment_mode'] === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="upi" {{ $filters['payment_mode'] === 'upi' ? 'selected' : '' }}>UPI</option>
                        <option value="card" {{ $filters['payment_mode'] === 'card' ? 'selected' : '' }}>Card</option>
                        <option value="bank_transfer" {{ $filters['payment_mode'] === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cheque" {{ $filters['payment_mode'] === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="other" {{ $filters['payment_mode'] === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Collected By</label>
                    <select name="collected_by" class="form-select">
                        <option value="">All</option>
                        @foreach ($staffMembers as $staff)
                            <option value="{{ $staff->id }}" {{ $filters['collected_by'] === (string) $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                        @endforeach
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
                        <th>Date</th>
                        <th>Student</th>
                        <th>Receipt No.</th>
                        <th class="text-end">Amount</th>
                        <th>Mode</th>
                        <th>Collected By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr class="{{ $transaction->isVoided() ? 'text-muted' : '' }}">
                            <td>{{ $transactions->firstItem() + $loop->index }}</td>
                            <td class="{{ $transaction->isVoided() ? 'text-decoration-line-through' : '' }}">{{ $transaction->payment_date->format('d M Y') }}</td>
                            <td class="{{ $transaction->isVoided() ? 'text-decoration-line-through' : '' }}">{{ $transaction->studentFee->student->name }}</td>
                            <td><code>{{ $transaction->receipt_number }}</code></td>
                            <td class="text-end {{ $transaction->isVoided() ? 'text-decoration-line-through' : '' }}">₹{{ number_format($transaction->amount, 2) }}</td>
                            <td>{{ $transaction->paymentModeLabel() }}</td>
                            <td>{{ $transaction->collectedBy?->name ?? '—' }}</td>
                            <td>
                                @if ($transaction->isVoided())
                                    <span class="badge bg-danger">VOIDED</span>
                                @else
                                    <span class="badge bg-success">Active</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No transactions match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Total Collected (excl. voided)</td>
                        <td class="text-end fw-semibold">₹{{ number_format($totalCollected, 2) }}</td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $transactions->links() }}
    </div>
</x-tenant-layout>
