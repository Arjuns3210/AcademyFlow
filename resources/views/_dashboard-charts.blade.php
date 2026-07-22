@if ($monthlyCollectionChart || $admissionTrendChart)
<div class="row g-4 mt-2">
    @if ($monthlyCollectionChart)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-2">Monthly Collection <span class="text-muted">(Last 6 Months)</span></div>
                    <canvas id="monthlyCollectionChart" height="220"></canvas>
                </div>
            </div>
        </div>
    @endif

    @if ($admissionTrendChart)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-2">Admission Trend <span class="text-muted">(Last 6 Months)</span></div>
                    <canvas id="admissionTrendChart" height="220"></canvas>
                </div>
            </div>
        </div>
    @endif
</div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if ($monthlyCollectionChart)
                    new Chart(document.getElementById('monthlyCollectionChart'), {
                        type: 'bar',
                        data: {
                            labels: @json(collect($monthlyCollectionChart)->pluck('label')),
                            datasets: [{
                                label: 'Collected (₹)',
                                data: @json(collect($monthlyCollectionChart)->pluck('value')),
                                backgroundColor: '#0d6efd',
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true } },
                        },
                    });
                @endif

                @if ($admissionTrendChart)
                    new Chart(document.getElementById('admissionTrendChart'), {
                        type: 'bar',
                        data: {
                            labels: @json(collect($admissionTrendChart)->pluck('label')),
                            datasets: [{
                                label: 'Admissions',
                                data: @json(collect($admissionTrendChart)->pluck('value')),
                                backgroundColor: '#198754',
                            }],
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        },
                    });
                @endif
            });
        </script>
    @endpush
@endif
