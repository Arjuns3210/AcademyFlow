<x-dynamic-component :component="auth()->user()->isSuperAdmin() ? 'super-admin-layout' : 'tenant-layout'">
    <x-slot name="header">
        <h2 class="h4 mb-0">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    @if (auth()->user()->isSuperAdmin())
        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Total Institutes</div>
                        <div class="fs-2 fw-semibold">{{ $superAdminStats['totalInstitutes'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Active Subscriptions</div>
                        <div class="fs-2 fw-semibold text-success">{{ $superAdminStats['activeSubscriptions'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Trial Institutes</div>
                        <div class="fs-2 fw-semibold text-warning">{{ $superAdminStats['trialInstitutes'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Expired / Cancelled</div>
                        <div class="fs-2 fw-semibold text-danger">{{ $superAdminStats['expiredOrCancelled'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Monthly Recurring Revenue</div>
                        <div class="fs-2 fw-semibold">₹{{ number_format($superAdminStats['mrr'], 2) }}</div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row g-4 mt-2">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-2">New Institute Signups <span class="text-muted">(Last 6 Months)</span></div>
                        <canvas id="instituteSignupChart" height="220"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-2">Institute Types <span class="text-muted">(All Institutes)</span></div>
                        <canvas id="instituteTypeChart" height="220"></canvas>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    new Chart(document.getElementById('instituteSignupChart'), {
                        type: 'bar',
                        data: {
                            labels: @json(collect($instituteSignupChart)->pluck('label')),
                            datasets: [{
                                label: 'New Institutes',
                                data: @json(collect($instituteSignupChart)->pluck('value')),
                                backgroundColor: '#6f42c1',
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });

                    new Chart(document.getElementById('instituteTypeChart'), {
                        type: 'doughnut',
                        data: {
                            labels: @json(collect($instituteTypeChart)->map(fn ($row) => "{$row['label']} ({$row['value']})")),
                            datasets: [{
                                data: @json(collect($instituteTypeChart)->pluck('value')),
                                backgroundColor: ['#0d6efd', '#6f42c1', '#20c997', '#fd7e14', '#dc3545', '#0dcaf0', '#ffc107', '#6c757d'],
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'right' } },
                        },
                    });
                });
            </script>
        @endpush
    @else
        @if ($trialBanner)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ $trialBanner['daysLeft'] }} {{ \Illuminate\Support\Str::plural('day', $trialBanner['daysLeft']) }} left in your free trial.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-3">
            @if ($studentStats)
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Total Students</div>
                            <div class="fs-2 fw-semibold">{{ $studentStats['total'] }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Active Students</div>
                            <div class="fs-2 fw-semibold">{{ $studentStats['active'] }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($feeStats)
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Pending Fees</div>
                            <div class="fs-2 fw-semibold {{ $feeStats['pending'] > 0 ? 'text-danger' : '' }}">₹{{ number_format($feeStats['pending'], 2) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Today's Collection</div>
                            <div class="fs-2 fw-semibold">₹{{ number_format($feeStats['todayCollection'], 2) }}</div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($attendanceStats)
                <div class="col-sm-6 col-xl-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Today's Attendance</div>
                            @if ($attendanceStats['markedToday'])
                                <div class="fs-2 fw-semibold">{{ $attendanceStats['present'] }} / {{ $attendanceStats['total'] }}</div>
                            @else
                                <div class="fs-4 fw-semibold text-muted">Not marked yet</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>

        @canAccessModule('dashboard_charts')
            @include('_dashboard-charts')
        @else
            @if ($monthlyCollectionChart || $admissionTrendChart || $studentsByCourseChart)
                <div class="row g-4 mt-2">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-4">
                                <i class="bi bi-lock text-muted fs-3 d-block mb-2"></i>
                                <p class="mb-2">Unlock revenue, admission trend &amp; course breakdown charts on the Professional plan.</p>
                                <a href="{{ route('institute.feature-locked', ['moduleKey' => 'dashboard_charts']) }}" class="btn btn-sm btn-outline-primary">
                                    Upgrade
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endcanAccessModule
    @endif
</x-dynamic-component>
