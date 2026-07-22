<x-tenant-layout>
    <x-slot name="header">
        <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
    </x-slot>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2">Student Report</h3>
                    <p class="text-muted small mb-3">Filterable student list with course, batch, status, admission date, pending fees, and this month's attendance %.</p>
                    <a href="{{ route('institute.reports.students') }}" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2">Attendance Report</h3>
                    <p class="text-muted small mb-3">Summary or day-by-day attendance breakdown for a date range.</p>
                    <a href="{{ route('institute.reports.attendance') }}" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2">Fee Report</h3>
                    <p class="text-muted small mb-3">Fee status per student — total, discount, paid, and pending amounts.</p>
                    <a href="{{ route('institute.reports.fees') }}" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2">Collection Report</h3>
                    <p class="text-muted small mb-3">Individual payment transactions within a date range.</p>
                    <a href="{{ route('institute.reports.collections') }}" class="btn btn-primary btn-sm">Open</a>
                </div>
            </div>
        </div>
    </div>
</x-tenant-layout>
