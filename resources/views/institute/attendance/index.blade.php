<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('institute.attendance.export', ['date' => $date, 'batch_id' => $batchId]) }}" class="btn btn-outline-secondary btn-sm">Export CSV</a>
                <a href="{{ route('institute.attendance.monthly') }}" class="btn btn-outline-secondary btn-sm">Monthly View</a>
            </div>
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

    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('institute.attendance.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Batch</label>
                    <select name="batch_id" class="form-select">
                        <option value="">All active students</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" {{ $batchId === (string) $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary w-100">Go</button>
                </div>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">
            {{ $markedCount }} of {{ $totalCount }} marked for {{ \Illuminate\Support\Carbon::parse($date)->format('d M Y') }}
            @if (! $isToday) <span class="badge bg-secondary">Past date</span> @endif
        </div>
        @if ($canSubmit && $totalCount > 0)
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAll('present')">Mark all present</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAll('absent')">Mark all absent</button>
            </div>
        @endif
    </div>

    <form action="{{ route('institute.attendance.store') }}" method="POST">
        @csrf
        <input type="hidden" name="attendance_date" value="{{ $date }}">
        <input type="hidden" name="batch_id" value="{{ $batchId }}">

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Student</th>
                            <th>Batch</th>
                            <th style="width: 200px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php $existing = $existingMarks->get($student->id); @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    {{ $student->name }}
                                    <div class="text-muted small"><code>{{ $student->student_code }}</code></div>
                                </td>
                                <td>{{ $student->batch?->name ?? '—' }}</td>
                                <td>
                                    @if ($canSubmit)
                                        <select name="statuses[{{ $student->id }}]" class="form-select form-select-sm attendance-status">
                                            <option value="present" {{ ($existing->status ?? 'present') === 'present' ? 'selected' : '' }}>Present</option>
                                            <option value="absent" {{ ($existing->status ?? '') === 'absent' ? 'selected' : '' }}>Absent</option>
                                            <option value="late" {{ ($existing->status ?? '') === 'late' ? 'selected' : '' }}>Late</option>
                                            <option value="leave" {{ ($existing->status ?? '') === 'leave' ? 'selected' : '' }}>Leave</option>
                                        </select>
                                    @else
                                        <span class="badge {{ match($existing->status ?? 'present') { 'present' => 'bg-success', 'late' => 'bg-warning text-dark', 'leave' => 'bg-info text-dark', 'absent' => 'bg-danger' } }}">
                                            {{ ucfirst($existing->status ?? 'Not marked') }}
                                        </span>
                                    @endif

                                    @canAccessModule('whatsapp_reminder')
                                        @if (($existing->status ?? '') === 'absent')
                                            {{-- Submits the standalone per-row form emitted after the main
                                                form closes below, via the HTML5 form= attribute — this form
                                                cannot literally nest inside the attendance-marking form
                                                itself, since HTML disallows that entirely. --}}
                                            <button type="submit" form="reminder-form-{{ $student->id }}" class="btn btn-sm btn-outline-secondary ms-1">
                                                Send Reminder
                                            </button>
                                        @endif
                                    @endcanAccessModule
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No active students to mark.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canSubmit && $totalCount > 0)
            <div class="mt-3">
                <x-primary-button>Save Attendance</x-primary-button>
            </div>
        @endif
    </form>

    @canAccessModule('whatsapp_reminder')
        @foreach ($students as $student)
            @php $existing = $existingMarks->get($student->id); @endphp
            @if (($existing->status ?? '') === 'absent')
                <form id="reminder-form-{{ $student->id }}" action="{{ route('institute.attendance.send-reminder', $student) }}" method="POST" class="d-none">
                    @csrf
                    <input type="hidden" name="attendance_date" value="{{ $date }}">
                </form>
            @endif
        @endforeach
    @endcanAccessModule

    @if ($canSubmit)
        <script>
            function markAll(status) {
                document.querySelectorAll('.attendance-status').forEach(function (select) {
                    select.value = status;
                });
            }
        </script>
    @endif
</x-tenant-layout>
