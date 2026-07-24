<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Edit Role</h2>
            <x-back-link :href="route('institute.roles.index')" />
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('institute.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')
                @include('institute.roles._form')

                <div class="d-flex gap-2 mt-3">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('institute.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
