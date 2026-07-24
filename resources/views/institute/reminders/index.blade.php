<x-tenant-layout>
    <x-slot name="header">
        <h2 class="h4 mb-0">Send Reminder</h2>
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

    @if ($canFees && $canAttendance)
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="fee-tab" data-bs-toggle="tab" data-bs-target="#fee-pane" type="button" role="tab">
                    Fee Reminders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance-pane" type="button" role="tab">
                    Attendance Reminders
                </button>
            </li>
        </ul>
    @endif

    <div class="tab-content">
        @if ($canFees)
            <div class="tab-pane fade show active" id="fee-pane" role="tabpanel">
                @if (! $canAttendance)
                    <h3 class="h6 text-muted mb-3">Fee Reminders</h3>
                @endif

                <form action="{{ route('institute.fees.send-bulk-reminders') }}" method="POST" id="fee-reminder-form">
                    @csrf

                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto text-muted small" id="fee-reminder-count">No students selected.</div>
                                <div class="col-md-3">
                                    <input type="text" name="due_date_label" class="form-control form-control-sm" placeholder="Due date label (optional)">
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" id="fee-reminder-submit" disabled>
                                        Send Reminder to Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Sr No</th>
                                        <th style="width: 32px;">
                                            <input type="checkbox" class="form-check-input" id="fee-select-all">
                                        </th>
                                        <th>Student</th>
                                        <th class="text-end">Pending Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pendingFees as $fee)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <input type="checkbox" class="form-check-input fee-reminder-checkbox" name="student_fee_ids[]" value="{{ $fee->id }}">
                                            </td>
                                            <td>
                                                {{ $fee->student->name }}
                                                <div class="text-muted small"><code>{{ $fee->student->student_code }}</code></div>
                                            </td>
                                            <td class="text-end text-danger fw-semibold">₹{{ number_format($fee->pending_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No pending fees right now.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        @if ($canAttendance)
            <div class="tab-pane fade {{ ! $canFees ? 'show active' : '' }}" id="attendance-pane" role="tabpanel">
                @if (! $canFees)
                    <h3 class="h6 text-muted mb-3">Attendance Reminders</h3>
                @endif

                <div class="card mb-3">
                    <div class="card-body">
                        <form action="{{ route('institute.reminders.index') }}" method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Date</label>
                                <input type="date" name="attendance_date" class="form-control" value="{{ $attendanceDate }}" max="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-secondary w-100">Go</button>
                            </div>
                        </form>
                    </div>
                </div>

                <form action="{{ route('institute.attendance.send-bulk-reminders') }}" method="POST" id="attendance-reminder-form">
                    @csrf
                    <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">

                    <div class="card mb-3">
                        <div class="card-body py-2">
                            <div class="row g-2 align-items-center">
                                <div class="col-auto text-muted small" id="attendance-reminder-count">No students selected.</div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" id="attendance-reminder-submit" disabled>
                                        Send Reminder to Selected
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Sr No</th>
                                        <th style="width: 32px;">
                                            <input type="checkbox" class="form-check-input" id="attendance-select-all">
                                        </th>
                                        <th>Student</th>
                                        <th>Batch</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($absentStudents as $student)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <input type="checkbox" class="form-check-input attendance-reminder-checkbox" name="student_ids[]" value="{{ $student->id }}">
                                            </td>
                                            <td>
                                                {{ $student->name }}
                                                <div class="text-muted small"><code>{{ $student->student_code }}</code></div>
                                            </td>
                                            <td>{{ $student->batch?->name ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">No absent students on {{ \Illuminate\Support\Carbon::parse($attendanceDate)->format('d M Y') }}.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <script>
        (function () {
            function wireBulkSection(checkboxSelector, selectAllId, submitId, countId, noun) {
                const selectAll = document.getElementById(selectAllId);
                const submitBtn = document.getElementById(submitId);
                const countLabel = document.getElementById(countId);

                if (! submitBtn) return;

                function checkboxes() {
                    return document.querySelectorAll(checkboxSelector);
                }

                function refresh() {
                    const checked = document.querySelectorAll(checkboxSelector + ':checked').length;
                    submitBtn.disabled = checked === 0;
                    countLabel.textContent = checked === 0 ? 'No students selected.' : checked + ' ' + noun + ' selected.';
                }

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        checkboxes().forEach(function (cb) { cb.checked = selectAll.checked; });
                        refresh();
                    });
                }

                checkboxes().forEach(function (cb) {
                    cb.addEventListener('change', refresh);
                });

                refresh();
            }

            wireBulkSection('.fee-reminder-checkbox', 'fee-select-all', 'fee-reminder-submit', 'fee-reminder-count', 'student(s)');
            wireBulkSection('.attendance-reminder-checkbox', 'attendance-select-all', 'attendance-reminder-submit', 'attendance-reminder-count', 'student(s)');
        })();
    </script>
</x-tenant-layout>
