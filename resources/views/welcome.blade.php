<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AcademyFlow') }}</title>
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="landing-hero d-flex flex-column align-items-center justify-content-center">
            <div class="card landing-card border-0 shadow-lg">
                <div class="card-body text-center p-5">
                    <div class="landing-icon-badge d-inline-flex align-items-center justify-content-center mb-3">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>

                    <x-application-logo size="fs-2" class="mb-2 d-inline-flex justify-content-center w-100" />

                    <p class="text-muted mb-4">
                        One place to run your institute — students, fees, attendance, and reports.
                    </p>

                    <div class="row row-cols-2 g-3 text-start small mb-4">
                        <div class="col d-flex align-items-center gap-2">
                            <i class="bi bi-people-fill text-primary"></i> Students &amp; Admissions
                        </div>
                        <div class="col d-flex align-items-center gap-2">
                            <i class="bi bi-cash-coin text-primary"></i> Fees &amp; Collections
                        </div>
                        <div class="col d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-check text-primary"></i> Attendance Tracking
                        </div>
                        <div class="col d-flex align-items-center gap-2">
                            <i class="bi bi-bar-chart-line text-primary"></i> Reports &amp; Insights
                        </div>
                    </div>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg px-4">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4">Log in</a>
                    @endauth
                </div>
            </div>

            <p class="text-muted small mt-3 mb-0">Powered by MP Tech Solutions</p>
        </div>
    </body>
</html>
