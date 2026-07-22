<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Fee Report</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('institute.reports.fees.export', $filters) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                <a href="{{ route('institute.reports.fees.export-pdf', $filters) }}" class="btn btn-outline-secondary btn-sm">Export PDF</a>
                <a href="{{ route('institute.reports.index') }}" class="btn btn-outline-secondary btn-sm">All Reports</a>
            </div>
        </div>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.reports.fees') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ $filters['status'] === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="partial" {{ $filters['status'] === 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="paid" {{ $filters['status'] === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Course</label>
                    <select name="course_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" {{ $filters['course_id'] === (string) $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Batch</label>
                    <select name="batch_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $filters['batch_id'] === (string) $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Admitted From</label>
                    <input type="date" name="admission_from" class="form-control" value="{{ $filters['admission_from'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Admitted To</label>
                    <input type="date" name="admission_to" class="form-control" value="{{ $filters['admission_to'] }}">
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
                        <th>Course</th>
                        <th>Batch</th>
                        <th class="text-end">Total Fee</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Net Fee</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Pending</th>
                        <th>Status</th>
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
                            <td>{{ $fee->student->course?->name ?? '—' }}</td>
                            <td>{{ $fee->student->batch?->name ?? '—' }}</td>
                            <td class="text-end">₹{{ number_format($fee->total_fee, 2) }}</td>
                            <td class="text-end">₹{{ number_format($fee->discount_amount, 2) }}</td>
                            <td class="text-end">₹{{ number_format($fee->net_fee, 2) }}</td>
                            <td class="text-end">₹{{ number_format($fee->paid_amount, 2) }}</td>
                            <td class="text-end {{ $fee->pending_amount > 0 ? 'text-danger fw-semibold' : '' }}">₹{{ number_format($fee->pending_amount, 2) }}</td>
                            <td>
                                <span class="badge {{ match($fee->status) { 'paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'pending' => 'bg-danger' } }}">
                                    {{ ucfirst($fee->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No fee records match these filters.</td>
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
