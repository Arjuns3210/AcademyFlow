<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Module</h2>
            <x-back-link :href="route('super-admin.modules.index')" label="Modules" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form action="{{ route('super-admin.modules.store') }}" method="POST">
                @csrf
                @include('super-admin.modules._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Create Module</x-primary-button>
                    <a href="{{ route('super-admin.modules.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-super-admin-layout>
