<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Student Report</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('institute.reports.students.export', $filters) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                <a href="{{ route('institute.reports.students.export-pdf', $filters) }}" class="btn btn-outline-secondary btn-sm">Export PDF</a>
                <x-back-link :href="route('institute.reports.index')" />
            </div>
        </div>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.reports.students') }}" method="GET" class="row g-2 align-items-end">
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
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="dropped" {{ $filters['status'] === 'dropped' ? 'selected' : '' }}>Dropped</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Admitted From</label>
                    <input type="date" name="admission_from" class="form-control" value="{{ $filters['admission_from'] }}" min="1900-01-01" data-range-group="admission" data-range-role="from">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Admitted To</label>
                    <input type="date" name="admission_to" class="form-control" value="{{ $filters['admission_to'] }}" min="1900-01-01" data-range-group="admission" data-range-role="to">
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
                        <th>Code</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Batch</th>
                        <th>Admitted</th>
                        <th>Status</th>
                        <th class="text-end">Pending Fee</th>
                        <th class="text-end">Attendance % (This Month)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $row)
                        <tr>
                            <td>{{ $students->firstItem() + $loop->index }}</td>
                            <td><code>{{ $row['student']->student_code }}</code></td>
                            <td>{{ $row['student']->name }}</td>
                            <td>{{ $row['student']->course?->name ?? '—' }}</td>
                            <td>{{ $row['student']->batch?->name ?? '—' }}</td>
                            <td>{{ $row['student']->admission_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ match($row['student']->status) { 'active' => 'bg-success', 'inactive' => 'bg-secondary', 'dropped' => 'bg-danger' } }}">
                                    {{ ucfirst($row['student']->status) }}
                                </span>
                            </td>
                            <td class="text-end {{ $row['pendingFee'] > 0 ? 'text-danger fw-semibold' : '' }}">
                                {{ $row['pendingFee'] !== null ? '₹'.number_format($row['pendingFee'], 2) : '—' }}
                            </td>
                            <td class="text-end">{{ $row['attendancePercentage'] !== null ? $row['attendancePercentage'].'%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No students match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $students->links() }}
    </div>
</x-tenant-layout>
