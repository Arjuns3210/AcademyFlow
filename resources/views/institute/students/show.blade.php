<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $student->name }}</h2>
            <div class="d-flex gap-2">
                <x-back-link :href="route('institute.students.index')" label="Students" />
                @canModule('students', 'status')
                    @include('institute.students._status-form', ['student' => $student])
                @endcanModule
                @canModule('students', 'edit')
                    <a href="{{ route('institute.students.edit', $student) }}" class="btn btn-primary btn-sm">Edit</a>
                @endcanModule
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Student Details</h3>

                    @if ($student->photo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($student->photo) }}" alt="{{ $student->name }}" style="height: 80px; width: 80px; object-fit: cover; border-radius: 4px;" class="mb-3">
                    @endif

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Student Code</dt>
                        <dd class="col-sm-8"><code>{{ $student->student_code }}</code></dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ match($student->status) { 'active' => 'bg-success', 'inactive' => 'bg-secondary', 'dropped' => 'bg-danger' } }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Date of Birth</dt>
                        <dd class="col-sm-8">{{ $student->dob?->format('d M Y') ?? '—' }}</dd>

                        <dt class="col-sm-4">Gender</dt>
                        <dd class="col-sm-8">{{ $student->gender ? ucfirst($student->gender) : '—' }}</dd>

                        <dt class="col-sm-4">Admission Date</dt>
                        <dd class="col-sm-8">{{ $student->admission_date->format('d M Y') }}</dd>

                        <dt class="col-sm-4">Course</dt>
                        <dd class="col-sm-8">{{ $student->course?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Batch</dt>
                        <dd class="col-sm-8">
                            {{ $student->batch?->name ?? '—' }}
                            @if ($student->batch)
                                <span class="text-muted">({{ $student->batch->time_slot }})</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $student->address ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Parent / Guardian</h3>

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $student->parent_name ?: '—' }}</dd>

                        <dt class="col-sm-4">Mobile</dt>
                        <dd class="col-sm-8">{{ $student->parent_mobile }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $student->parent_email ?: '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        @if ($feeSummary)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="h6 text-uppercase text-muted mb-0">Fee History</h3>
                            <a href="{{ route('institute.fees.show', $feeSummary) }}" class="small">Full view &rarr;</a>
                        </div>

                        <dl class="row mb-3">
                            <dt class="col-sm-6">Net Fee</dt>
                            <dd class="col-sm-6 text-end">₹{{ number_format($feeSummary->net_fee, 2) }}</dd>

                            <dt class="col-sm-6">Paid</dt>
                            <dd class="col-sm-6 text-end">₹{{ number_format($feeSummary->paid_amount, 2) }}</dd>

                            <dt class="col-sm-6 fw-semibold">Pending</dt>
                            <dd class="col-sm-6 text-end fw-semibold {{ $feeSummary->pending_amount > 0 ? 'text-danger' : '' }}">
                                ₹{{ number_format($feeSummary->pending_amount, 2) }}
                            </dd>

                            <dt class="col-sm-6">Status</dt>
                            <dd class="col-sm-6 text-end">
                                <span class="badge {{ match($feeSummary->status) { 'paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'pending' => 'bg-danger' } }}">
                                    {{ ucfirst($feeSummary->status) }}
                                </span>
                            </dd>
                        </dl>

                        @if ($recentTransactions->isNotEmpty())
                            <hr class="my-2">
                            <div class="small text-muted text-uppercase mb-2">Recent Payments</div>
                            <ul class="list-unstyled mb-0 small">
                                @foreach ($recentTransactions as $transaction)
                                    <li class="d-flex justify-content-between {{ $transaction->isVoided() ? 'text-muted text-decoration-line-through' : '' }}">
                                        <span>{{ $transaction->payment_date->format('d M Y') }} &middot; {{ $transaction->paymentModeLabel() }}</span>
                                        <span>₹{{ number_format($transaction->amount, 2) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted small mb-0">No payments recorded yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($attendanceSummary)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="h6 text-uppercase text-muted mb-0">Attendance — {{ now()->format('F Y') }}</h3>
                            <a href="{{ route('institute.attendance.monthly', ['student_id' => $student->id]) }}" class="small">Full view &rarr;</a>
                        </div>

                        <dl class="row mb-0">
                            <dt class="col-sm-6">Present</dt>
                            <dd class="col-sm-6 text-end">{{ $attendanceSummary['present'] }}</dd>

                            <dt class="col-sm-6">Absent</dt>
                            <dd class="col-sm-6 text-end">{{ $attendanceSummary['absent'] }}</dd>

                            <dt class="col-sm-6">Late</dt>
                            <dd class="col-sm-6 text-end">{{ $attendanceSummary['late'] }}</dd>

                            <dt class="col-sm-6">Leave</dt>
                            <dd class="col-sm-6 text-end">{{ $attendanceSummary['leave'] }}</dd>

                            <dt class="col-sm-6 fw-semibold">% Attendance</dt>
                            <dd class="col-sm-6 text-end fw-semibold">
                                {{ $attendanceSummary['percentage'] !== null ? $attendanceSummary['percentage'].'%' : '—' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        @endif
    </div>


</x-tenant-layout>
