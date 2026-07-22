<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Edit Institute</h2>
            <x-back-link :href="route('super-admin.institutes.index')" label="Institutes" />
        </div>
    </x-slot>

    @if (session('newAdminPassword'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>New password generated:</strong>
            <code>{{ session('newAdminPassword') }}</code>
            &mdash; this is shown once and cannot be retrieved again. Share it with the Institute Admin securely.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3" style="max-width: 900px;">
        <div class="card-body">
            <form action="{{ route('super-admin.institutes.update', $institute) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('super-admin.institutes._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('super-admin.institutes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <h3 class="h6 text-uppercase text-muted mb-3">Institute Admin</h3>

            @if ($admin)
                <div class="d-flex align-items-center justify-content-between border rounded p-3 mb-0">
                    <div>
                        <div class="fw-semibold">{{ $admin->name }}</div>
                        <div class="text-muted small">{{ $admin->email }}</div>
                    </div>
                    <form action="{{ route('super-admin.institutes.reset-admin-password', $institute) }}" method="POST" onsubmit="return confirm('Generate a new password for this Institute Admin?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Reset Password</button>
                    </form>
                </div>
            @else
                <p class="text-muted mb-0">No Institute Admin user found for this institute.</p>
            @endif
        </div>
    </div>
</x-super-admin-layout>
