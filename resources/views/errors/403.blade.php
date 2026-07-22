<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Access Denied') }} — {{ config('app.name', 'AcademyFlow') }}</title>
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="bg-light">
        <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center py-4">
            <div class="mb-4">
                <x-application-logo />
            </div>

            <div class="card shadow-sm" style="width: 100%; max-width: 480px;">
                <div class="card-body p-4 text-center">
                    <div class="display-4 text-danger mb-3">403</div>
                    <h1 class="h5 mb-3">{{ __('Access Denied') }}</h1>
                    <p class="text-muted mb-4">
                        {{ $exception->getMessage() ?: __("You don't have permission to access this page.") }}
                    </p>

                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary">{{ __('Back to Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary">{{ __('Log in') }}</a>
                    @endauth
                </div>
            </div>
        </div>
    </body>
</html>
