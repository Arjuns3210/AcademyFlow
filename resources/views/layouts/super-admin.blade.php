<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <title>{{ config('app.name', 'AcademyFlow') }} — Super Admin</title>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="bg-light">
        <div class="d-flex">
            <nav class="sidebar bg-dark flex-shrink-0" style="width: 240px;">
                <div class="p-3 border-bottom border-secondary">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">
                        <x-application-logo variant="light" size="fs-5" />
                    </a>
                    <div class="small text-white-50 mt-1">Super Admin</div>
                </div>

                <ul class="nav flex-column p-2">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-speedometer2 me-1"></i> {{ __('Dashboard') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super-admin.institutes.index') }}" class="nav-link {{ request()->routeIs('super-admin.institutes.*') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-building me-1"></i> {{ __('Institutes') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super-admin.institute-types.index') }}" class="nav-link {{ request()->routeIs('super-admin.institute-types.*') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-diagram-3 me-1"></i> {{ __('Institute Types') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super-admin.modules.index') }}" class="nav-link {{ request()->routeIs('super-admin.modules.*') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-grid-3x3-gap me-1"></i> {{ __('Modules') }}
                        </a>
                    </li>

                    <li><hr class="text-white-50"></li>

                    <li class="nav-item">
                        <a href="{{ route('super-admin.plans.index') }}" class="nav-link {{ request()->routeIs('super-admin.plans.*') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-credit-card me-1"></i> {{ __('Plans') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super-admin.subscriptions.index') }}" class="nav-link {{ request()->routeIs('super-admin.subscriptions.*') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-arrow-repeat me-1"></i> {{ __('Subscriptions') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('super-admin.coupons.index') }}" class="nav-link {{ request()->routeIs('super-admin.coupons.*') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-tags me-1"></i> {{ __('Coupons') }}
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="flex-grow-1 min-vh-100 d-flex flex-column">
                <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                    <div class="container-fluid px-4 justify-content-end">
                        <ul class="navbar-nav">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('Profile') }}</a></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">{{ __('Log Out') }}</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>

                @isset($header)
                    <header class="bg-white border-bottom">
                        <div class="container-fluid px-4 py-3">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="container-fluid px-4 py-4 flex-grow-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
