<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Edit Staff Member</h2>
            <x-back-link :href="route('institute.staff.index')" />
        </div>
    </x-slot>

    @if (session('newStaffPassword'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>New password generated:</strong>
            <code>{{ session('newStaffPassword') }}</code>
            &mdash; this is shown once and cannot be retrieved again. Share it with the staff member securely.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form action="{{ route('institute.staff.update', $staffMember) }}" method="POST">
                @csrf
                @method('PUT')
                @include('institute.staff._form')

                <div class="d-flex gap-2 mb-3">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('institute.staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>

            @if ($staffMember->id !== auth()->id())
                <hr>
                <form action="{{ route('institute.staff.reset-password', $staffMember) }}" method="POST" onsubmit="return confirm('Generate a new password for this staff member?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Reset Password</button>
                </form>
            @endif
        </div>
    </div>
</x-tenant-layout>
