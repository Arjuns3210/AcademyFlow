<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $institute->name }}</h2>
            <div class="d-flex gap-2 align-items-center">
                <x-back-link :href="route('super-admin.institutes.index')" />
                <a href="{{ route('super-admin.institutes.edit', $institute) }}" class="btn btn-primary btn-sm">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Institute Details</h3>

                    @if ($institute->logo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($institute->logo) }}" alt="Logo" style="height: 64px;" class="mb-3">
                    @endif

                    <dl class="row mb-0">
                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8">{{ $institute->instituteType?->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ $institute->isActive() ? 'bg-success' : 'bg-danger' }}">
                                {{ $institute->isActive() ? 'Active' : 'Blocked' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $institute->address ?: '—' }}</dd>

                        <dt class="col-sm-4">Contact</dt>
                        <dd class="col-sm-8">{{ $institute->contact ?: '—' }}</dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $institute->email ?: '—' }}</dd>

                        <dt class="col-sm-4">GST Number</dt>
                        <dd class="col-sm-8">{{ $institute->gst_number ?: '—' }}</dd>

                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8">{{ $institute->created_at->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Institute Admin</h3>

                    @if ($admin)
                        <p class="mb-0">
                            <span class="fw-semibold">{{ $admin->name }}</span><br>
                            <span class="text-muted">{{ $admin->email }}</span>
                        </p>
                    @else
                        <p class="text-muted mb-0">No Institute Admin user found.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Enabled Modules</h3>

                    @php $enabledModules = $institute->modules->where('pivot.is_enabled', true); @endphp

                    @if ($enabledModules->isEmpty())
                        <p class="text-muted mb-0">No modules enabled.</p>
                    @else
                        <ul class="list-inline mb-0">
                            @foreach ($enabledModules as $module)
                                <li class="list-inline-item badge bg-secondary mb-1">
                                    {{ $module->pivot->custom_label ?: $module->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-super-admin-layout>
