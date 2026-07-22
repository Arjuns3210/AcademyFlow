<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Institute</h2>
            <x-back-link :href="route('super-admin.institutes.index')" label="Institutes" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form action="{{ route('super-admin.institutes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('super-admin.institutes._form', ['selectedModuleIds' => [], 'customLabels' => []])

                <hr class="my-4">

                <h3 class="h6 text-uppercase text-muted mb-3">First Institute Admin Login</h3>

                <div class="row">
                    <div class="col-md-6">
                        <x-form-input name="admin_name" label="Admin Name" required />
                    </div>
                    <div class="col-md-6">
                        <x-form-input name="admin_email" label="Admin Email" type="email" required />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <x-form-input name="admin_password" label="Password" type="password" required />
                    </div>
                    <div class="col-md-6">
                        <x-form-input name="admin_password_confirmation" label="Confirm Password" type="password" required />
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-primary-button>Create Institute</x-primary-button>
                    <a href="{{ route('super-admin.institutes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-super-admin-layout>
