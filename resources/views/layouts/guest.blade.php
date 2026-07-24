<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'AcademyFlow') }}</title>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="auth-shell d-flex">
            <div class="auth-brand-panel d-none d-lg-flex flex-column justify-content-center text-white">
                <i class="bi bi-mortarboard-fill auth-brand-icon"></i>

                <div class="position-relative">
                    <a href="/" class="text-decoration-none d-inline-block mb-4">
                        <x-application-logo variant="light" />
                    </a>

                    <p class="fs-4 fw-semibold mb-4">Run your institute, all in one place.</p>

                    <ul class="list-unstyled auth-feature-list mb-0">
                        <li><i class="bi bi-people-fill"></i> Students &amp; Admissions</li>
                        <li><i class="bi bi-cash-coin"></i> Fees &amp; Collections</li>
                        <li><i class="bi bi-calendar-check"></i> Attendance Tracking</li>
                        <li><i class="bi bi-bar-chart-line"></i> Reports &amp; Insights</li>
                    </ul>
                </div>
            </div>

            <div class="auth-form-panel d-flex flex-column justify-content-center align-items-center py-4">
                <a href="/" class="text-decoration-none d-lg-none mb-4">
                    <x-application-logo />
                </a>

                <div class="card auth-card shadow-sm border-0">
                    <div class="card-body p-4">
                        {{ $slot }}
                    </div>
                </div>

                <p class="text-muted small mt-4 mb-0">Powered by MP Tech Solutions</p>
            </div>
        </div>
    </body>
</html>
