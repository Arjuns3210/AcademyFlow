<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <title>{{ Auth::user()->institute?->name ?? config('app.name', 'AcademyFlow') }}</title>

        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body class="bg-light">
        <div class="d-flex">
            <nav class="sidebar flex-shrink-0 {{ Auth::user()->institute?->hasLightBackground() ? 'sidebar-light-theme' : '' }}" style="width: 240px; {{ Auth::user()->institute?->sidebarStyle() }}">
                <div class="p-3 border-bottom border-secondary">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none d-flex align-items-center gap-2">
                        @if (Auth::user()->institute?->logo)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url(Auth::user()->institute->logo) }}" alt="Logo" style="height: 32px; width: 32px; object-fit: cover; border-radius: 4px;">
                        @endif
                        <span class="fw-semibold">{{ Auth::user()->institute?->name ?? config('app.name', 'AcademyFlow') }}</span>
                    </a>
                </div>

                <ul class="nav flex-column p-2">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold' : '' }}">
                            <i class="bi bi-speedometer2 me-1"></i> {{ __('Dashboard') }}
                        </a>
                    </li>

                    @foreach ($sidebarModules as $instituteModule)
                        @if ($instituteModule->module->slug === 'settings')
                            <li class="nav-item">
                                <a href="{{ route('institute.roles.index') }}" class="nav-link {{ request()->routeIs('institute.roles.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi bi-person-badge me-1"></i> {{ __('Roles') }}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('institute.staff.index') }}" class="nav-link {{ request()->routeIs('institute.staff.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi bi-people me-1"></i> {{ __('Staff') }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'students')
                            <li class="nav-item">
                                <a href="{{ route('institute.students.index') }}" class="nav-link {{ request()->routeIs('institute.students.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'courses')
                            <li class="nav-item">
                                <a href="{{ route('institute.courses.index') }}" class="nav-link {{ request()->routeIs('institute.courses.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'teachers')
                            <li class="nav-item">
                                <a href="{{ route('institute.teachers.index') }}" class="nav-link {{ request()->routeIs('institute.teachers.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'batches')
                            <li class="nav-item">
                                <a href="{{ route('institute.batches.index') }}" class="nav-link {{ request()->routeIs('institute.batches.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'fees')
                            <li class="nav-item">
                                <a href="{{ route('institute.fees.index') }}" class="nav-link {{ request()->routeIs('institute.fees.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'attendance')
                            <li class="nav-item">
                                <a href="{{ route('institute.attendance.index') }}" class="nav-link {{ request()->routeIs('institute.attendance.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @elseif ($instituteModule->module->slug === 'reports')
                            <li class="nav-item">
                                <a href="{{ route('institute.reports.index') }}" class="nav-link {{ request()->routeIs('institute.reports.*') ? 'active fw-semibold' : '' }}">
                                    <i class="bi {{ $instituteModule->module->icon }} me-1"></i> {{ $instituteModule->label() }}
                                </a>
                            </li>
                        @else
                            <li class="nav-item">
                                <span class="nav-link disabled d-flex justify-content-between align-items-center">
                                    <span>
                                        @if ($instituteModule->module->icon)
                                            <i class="bi {{ $instituteModule->module->icon }} me-1"></i>
                                        @endif
                                        {{ $instituteModule->label() }}
                                    </span>
                                    <span class="badge bg-secondary">Soon</span>
                                </span>
                            </li>
                        @endif
                    @endforeach

                    @canAccessModule('whatsapp_reminder')
                        <li class="nav-item">
                            <a href="{{ route('institute.reminders.index') }}" class="nav-link {{ request()->routeIs('institute.reminders.*') ? 'active fw-semibold' : '' }}">
                                <i class="bi bi-send me-1"></i> {{ __('Send Reminder') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('institute.whatsapp.index') }}" class="nav-link {{ request()->routeIs('institute.whatsapp.*') ? 'active fw-semibold' : '' }}">
                                <i class="bi bi-whatsapp me-1"></i> {{ __('WhatsApp') }}
                            </a>
                        </li>
                    @endcanAccessModule
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
