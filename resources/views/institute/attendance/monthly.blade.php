<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $moduleLabel }} — Monthly View</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('institute.attendance.monthly.export', ['ym' => sprintf('%04d-%02d', $year, $month), 'student_id' => $studentId, 'batch_id' => $batchId]) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                <x-back-link :href="route('institute.attendance.index')" />
            </div>
        </div>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.attendance.monthly') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All active students</option>
                        @foreach ($allStudents as $student)
                            <option value="{{ $student->id }}" {{ $studentId === (string) $student->id ? 'selected' : '' }}>{{ $student->name }} ({{ $student->student_code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Or Batch</label>
                    <select name="batch_id" class="form-select">
                        <option value="">— any —</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $batchId === (string) $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Month</label>
                    <input type="month" name="ym" class="form-control" value="{{ sprintf('%04d-%02d', $year, $month) }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Go</button>
                </div>
            </form>
            <div class="text-muted small mt-2">
                Selecting a specific student takes priority over the batch filter. P = Present, A = Absent, L = Late, Lv = Leave.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0" style="overflow-x: auto;">
            <table class="table table-sm table-bordered mb-0 text-center align-middle" style="min-width: max-content;">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th class="text-start" style="position: sticky; left: 0; background: var(--bs-body-bg);">Student</th>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            <th style="width: 32px;">{{ $day }}</th>
                        @endfor
                        <th>P</th>
                        <th>A</th>
                        <th>L</th>
                        <th>Lv</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="text-start" style="position: sticky; left: 0; background: var(--bs-body-bg);">
                                {{ $row['student']->name }}
                                <div class="text-muted small"><code>{{ $row['student']->student_code }}</code></div>
                            </td>
                            @for ($day = 1; $day <= $daysInMonth; $day++)
                                @php $status = $row['days'][$day]; @endphp
                                <td class="{{ match($status) { 'present' => 'bg-success-subtle', 'absent' => 'bg-danger-subtle', 'late' => 'bg-warning-subtle', 'leave' => 'bg-info-subtle', default => '' } }}">
                                    {{ match($status) { 'present' => 'P', 'absent' => 'A', 'late' => 'L', 'leave' => 'Lv', default => '—' } }}
                                </td>
                            @endfor
                            <td>{{ $row['summary']['present'] }}</td>
                            <td>{{ $row['summary']['absent'] }}</td>
                            <td>{{ $row['summary']['late'] }}</td>
                            <td>{{ $row['summary']['leave'] }}</td>
                            <td class="fw-semibold">{{ $row['percentage'] !== null ? $row['percentage'].'%' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $daysInMonth + 7 }}" class="text-center text-muted py-4">No students to show.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-tenant-layout>
