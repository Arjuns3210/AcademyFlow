<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Role</h2>
            <x-back-link :href="route('institute.roles.index')" />
        </div>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('institute.roles.store') }}" method="POST">
                @csrf
                @include('institute.roles._form')

                <div class="d-flex gap-2 mt-3">
                    <x-primary-button>Create Role</x-primary-button>
                    <a href="{{ route('institute.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
