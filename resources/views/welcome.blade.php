<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <title>{{ config('app.name', 'AcademyFlow') }} — Institute Management Software</title>
        <meta name="description" content="AcademyFlow is an all-in-one platform for coaching institutes and academies to manage admissions, fees, attendance, and reports.">
        @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="landing-page">

            {{-- Nav --}}
            <nav class="landing-nav d-flex align-items-center justify-content-between">
                <x-application-logo />

                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary px-4">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary px-4">Log in</a>
                @endauth
            </nav>

            {{-- Hero --}}
            <header class="landing-hero text-center">
                <div class="landing-icon-badge d-inline-flex align-items-center justify-content-center mb-4">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>

                <h1 class="landing-headline mb-3">Run your entire institute<br class="d-none d-md-block"> from one dashboard</h1>

                <p class="landing-subheadline text-muted mx-auto mb-4">
                    Admissions, fees, attendance, and reports — built for coaching institutes and academies
                    that are done juggling registers, spreadsheets, and WhatsApp groups.
                </p>

                <div class="d-flex flex-wrap gap-3 justify-content-center mb-2">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg px-4">Go to Dashboard</a>
                    @else
                        <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#demoLoginModal">Try Live Demo</button>
                        <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg px-4">Log in</a>
                        <a href="https://mptechpoint.in/contact" target="_blank" rel="noopener" class="btn btn-outline-primary btn-lg px-4">Talk to Sales</a>
                    @endauth
                </div>
                <p class="text-muted small mb-0">Every plan starts with a 7-day free trial. New institutes are onboarded by our team — reach out to get set up.</p>
            </header>

            {{-- Core features --}}
            <section class="landing-section">
                <h2 class="landing-section-title text-center mb-2">Everything your institute runs on</h2>
                <p class="text-muted text-center mx-auto mb-5 landing-section-lead">
                    One connected system instead of five disconnected tools.
                </p>

                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                            <h3 class="h5 mb-2">Students &amp; Admissions</h3>
                            <p class="text-muted small mb-0">Admit, search, and manage students with course and batch assignment built in.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
                            <h3 class="h5 mb-2">Fees &amp; Collections</h3>
                            <p class="text-muted small mb-0">Track dues, collect payments, apply discounts, and generate PDF receipts automatically.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="feature-icon"><i class="bi bi-calendar-check"></i></div>
                            <h3 class="h5 mb-2">Attendance Tracking</h3>
                            <p class="text-muted small mb-0">Mark daily attendance in seconds and review monthly trends per student or batch.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="feature-icon"><i class="bi bi-bar-chart-line"></i></div>
                            <h3 class="h5 mb-2">Reports &amp; Insights</h3>
                            <p class="text-muted small mb-0">Student, attendance, fee, and collection reports — export to CSV or PDF anytime.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="feature-icon"><i class="bi bi-person-badge"></i></div>
                            <h3 class="h5 mb-2">Roles &amp; Staff Access</h3>
                            <p class="text-muted small mb-0">Give staff exactly the access they need with granular, per-module permissions.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card h-100">
                            <div class="feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                            <h3 class="h5 mb-2">Secure by Design</h3>
                            <p class="text-muted small mb-0">Every institute's data is fully isolated on a modern, actively maintained platform.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Product tour --}}
            <section class="landing-section">
                <h2 class="landing-section-title text-center mb-2">See AcademyFlow in action</h2>
                <p class="text-muted text-center mx-auto mb-5 landing-section-lead">
                    A real look at the dashboard your institute would use every day.
                </p>

                <div class="row g-4">
                    @foreach ([
                        ['dashboard.png', 'Dashboard', 'Students, fees, attendance, and collection trends at a glance.'],
                        ['students.png', 'Students', 'Search, filter, and manage every admission in one list.'],
                        ['batches.png', 'Batches', 'See enrollment, teacher, and timing for every batch.'],
                        ['fees.png', 'Fees', 'Track paid, partial, and pending fees across all students.'],
                        ['fee-detail.png', 'Fee Collection', 'Collect payments and auto-generate receipts per student.'],
                        ['attendance.png', 'Attendance', 'Mark daily attendance and send reminders to absentees.'],
                    ] as [$file, $title, $desc])
                        <div class="col-md-6 col-lg-4">
                            <div class="screenshot-card h-100">
                                <div class="screenshot-browser-bar">
                                    <span></span><span></span><span></span>
                                </div>
                                <img
                                    src="{{ asset('images/screenshots/' . $file) }}"
                                    alt="AcademyFlow {{ $title }} screen"
                                    class="screenshot-img"
                                    loading="lazy"
                                    role="button"
                                    data-full-src="{{ asset('images/screenshots/' . $file) }}"
                                    data-full-title="{{ $title }}"
                                >
                                <div class="p-3">
                                    <h3 class="h6 mb-1">{{ $title }}</h3>
                                    <p class="text-muted small mb-0">{{ $desc }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Pricing --}}
            <section class="landing-section landing-section-alt">
                <h2 class="landing-section-title text-center mb-2">Simple, transparent pricing</h2>
                <p class="text-muted text-center mx-auto mb-5 landing-section-lead">
                    Every plan includes the full core system and a 7-day free trial. Higher plans unlock automation add-ons.
                </p>

                @if ($plans->isEmpty())
                    <p class="text-muted text-center mb-0">Pricing is being finalized — contact us for current plan details.</p>
                @else
                    <div class="row g-4 justify-content-center">
                        @foreach ($plans as $plan)
                            @php
                                $isPopular = $plan->name === 'Professional';
                                $planModuleKeys = $plan->planModules->pluck('module_key');
                            @endphp
                            <div class="col-md-6 col-lg-4">
                                <div class="pricing-card h-100 {{ $isPopular ? 'pricing-card-popular' : '' }}">
                                    @if ($isPopular)
                                        <span class="pricing-badge">Most Popular</span>
                                    @endif

                                    <h3 class="h4 mb-1">{{ $plan->name }}</h3>
                                    @if ($plan->description)
                                        <p class="text-muted small mb-3">{{ $plan->description }}</p>
                                    @endif

                                    <div class="pricing-amount mb-3">
                                        <span class="pricing-currency">₹</span>{{ number_format((float) $plan->price) }}
                                        <span class="text-muted fs-6 fw-normal">/ {{ $plan->billing_cycle === 'yearly' ? 'year' : 'month' }}</span>
                                        @if ($plan->billing_cycle === 'yearly')
                                            <span class="badge bg-success align-middle">10% off</span>
                                        @endif
                                    </div>

                                    <ul class="list-unstyled pricing-feature-list mb-4">
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Full core system (students, fees, attendance, reports)</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> {{ $plan->staff_limit ? "Up to {$plan->staff_limit} staff accounts" : 'Unlimited staff accounts' }}</li>
                                        @forelse ($planModuleKeys as $key)
                                            <li><i class="bi bi-check-circle-fill text-success"></i> {{ $moduleLabels[$key] ?? $key }}</li>
                                        @empty
                                            <li class="text-muted"><i class="bi bi-dash-circle"></i> Add-on automation not included</li>
                                        @endforelse
                                    </ul>

                                    <a href="https://mptechpoint.in/contact" target="_blank" rel="noopener" class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100">Get Started</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Footer --}}
            <footer class="landing-footer text-center">
                <x-application-logo size="fs-5" class="justify-content-center mb-2" />
                <p class="text-muted small mb-1">Powered by <b><a href="https://mptechpoint.in/" target="_blank" rel="noopener" class="text-muted">Mp tech point</a></b></p>
                <p class="text-muted small mb-0">&copy; {{ now()->year }} Mp tech point. All rights reserved.</p>
            </footer>
        </div>

        {{-- Demo login: capture contact details before entering the shared demo --}}
        <div class="modal fade" id="demoLoginModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('demo-login') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Try the Live Demo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                Share your details and we'll drop you straight into a live, pre-filled demo institute.
                                This demo session lasts {{ \App\Http\Middleware\ExpireDemoSession::MAX_DEMO_SESSION_HOURS }} hours — a real account gets a full 7-day free trial.
                            </p>

                            <div class="mb-3">
                                <label class="form-label small mb-1" for="demo_name">Your Name</label>
                                <input type="text" id="demo_name" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" minlength="2" maxlength="100" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1" for="demo_phone">Phone Number</label>
                                <input type="tel" id="demo_phone" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone') }}" pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric"
                                    placeholder="10-digit mobile number" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @else
                                    <div class="form-text">Enter a 10-digit mobile number starting with 6-9.</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1" for="demo_institute_name">Institute Name</label>
                                <input type="text" id="demo_institute_name" name="institute_name" class="form-control @error('institute_name') is-invalid @enderror"
                                    value="{{ old('institute_name') }}" maxlength="225" required>
                                @error('institute_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-0">
                                <label class="form-label small mb-1" for="demo_email">Email</label>
                                <input type="email" id="demo_email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" maxlength="255" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary w-100">Enter Demo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Shared lightbox for the product tour screenshots --}}
        <div class="modal fade" id="screenshotModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="screenshotModalTitle"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <img id="screenshotModalImg" src="" alt="" class="w-100">
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                @if ($errors->any())
                    new bootstrap.Modal(document.getElementById('demoLoginModal')).show();
                @endif

                document.querySelectorAll('.screenshot-img').forEach(function (img) {
                    img.addEventListener('click', function () {
                        document.getElementById('screenshotModalImg').src = img.dataset.fullSrc;
                        document.getElementById('screenshotModalImg').alt = img.alt;
                        document.getElementById('screenshotModalTitle').textContent = img.dataset.fullTitle;
                        new bootstrap.Modal(document.getElementById('screenshotModal')).show();
                    });
                });
            });
        </script>
    </body>
</html>
