<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AcademyFlow') }}</title>
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="landing-hero d-flex align-items-center justify-content-center">
            <div class="card landing-card border-0 shadow-lg">
                <div class="card-body text-center p-5">
                    <x-application-logo size="fs-1" class="mb-2 d-inline-block" />
                    <p class="text-muted mb-4">
                        One place to run your institute — students, fees, attendance, and reports.
                    </p>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg px-4">Go to Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-4">Log in</a>
                    @endauth
                </div>
            </div>
        </div>
    </body>
</html>
