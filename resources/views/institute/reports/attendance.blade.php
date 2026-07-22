<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Attendance Report</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('institute.reports.attendance.export', array_merge($filters, ['mode' => $mode])) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                <a href="{{ route('institute.reports.attendance.export-pdf', array_merge($filters, ['mode' => $mode])) }}" class="btn btn-outline-secondary btn-sm">Export PDF</a>
                <a href="{{ route('institute.reports.index') }}" class="btn btn-outline-secondary btn-sm">All Reports</a>
            </div>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.reports.attendance') }}" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <div class="col-md-2">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $filters['from'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $filters['to'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All active students</option>
                        @foreach ($allStudents as $student)
                            <option value="{{ $student->id }}" {{ $filters['student_id'] === (string) $student->id ? 'selected' : '' }}>{{ $student->name }} ({{ $student->student_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Or Batch</label>
                    <select name="batch_id" class="form-select">
                        <option value="">— any —</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $filters['batch_id'] === (string) $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>
            <div class="text-muted small mt-2">Selecting a specific student takes priority over the batch filter.</div>
        </div>
    </div>

    <div class="mb-3">
        <div class="btn-group">
            <a href="{{ route('institute.reports.attendance', array_merge($filters, ['mode' => 'summary'])) }}" class="btn btn-sm {{ $mode === 'summary' ? 'btn-primary' : 'btn-outline-primary' }}">Summary</a>
            <a href="{{ route('institute.reports.attendance', array_merge($filters, ['mode' => 'detailed'])) }}" class="btn btn-sm {{ $mode === 'detailed' ? 'btn-primary' : 'btn-outline-primary' }}">Detailed</a>
        </div>
    </div>

    @if ($mode === 'summary')
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Student</th>
                            <th class="text-end">Present</th>
                            <th class="text-end">Absent</th>
                            <th class="text-end">Late</th>
                            <th class="text-end">Leave</th>
                            <th class="text-end">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaryRows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $row['student']->name }}
                                    <div class="text-muted small"><code>{{ $row['student']->student_code }}</code></div>
                                </td>
                                <td class="text-end">{{ $row['summary']['present'] }}</td>
                                <td class="text-end">{{ $row['summary']['absent'] }}</td>
                                <td class="text-end">{{ $row['summary']['late'] }}</td>
                                <td class="text-end">{{ $row['summary']['leave'] }}</td>
                                <td class="text-end fw-semibold">{{ $row['summary']['percentage'] !== null ? $row['summary']['percentage'].'%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No students match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        @if ($rangeTooLarge)
            <div class="alert alert-warning">
                This range spans {{ $daysInRange }} days. Detailed view is limited to 31 days at a time — please narrow your date range, or use Summary view for longer periods.
            </div>
        @else
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($detailRows as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $row['student']->name }}
                                        <div class="text-muted small"><code>{{ $row['student']->student_code }}</code></div>
                                    </td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                                    <td>
                                        @if ($row['status'])
                                            <span class="badge {{ match($row['status']) { 'present' => 'bg-success', 'absent' => 'bg-danger', 'late' => 'bg-warning text-dark', 'leave' => 'bg-info text-dark' } }}">
                                                {{ ucfirst($row['status']) }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not marked</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No students match these filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</x-tenant-layout>
