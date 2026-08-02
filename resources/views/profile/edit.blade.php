<x-dynamic-component :component="auth()->user()->isSuperAdmin() ? 'super-admin-layout' : 'tenant-layout'">
    <x-slot name="header">
        <h2 class="h4 mb-0">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="d-flex flex-column gap-3" style="max-width: 640px;">
        @if (auth()->user()->is_demo_account)
            <div class="alert alert-info mb-0">
                Profile, password, and account-deletion changes are disabled for this shared demo account so every visitor sees the same live demo.
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        @endif
    </div>
</x-dynamic-component>
