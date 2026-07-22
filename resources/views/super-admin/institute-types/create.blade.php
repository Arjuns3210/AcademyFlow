<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Institute Type</h2>
            <x-back-link :href="route('super-admin.institute-types.index')" label="Institute Types" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form action="{{ route('super-admin.institute-types.store') }}" method="POST">
                @csrf
                @include('super-admin.institute-types._form', ['selectedModuleIds' => []])

                <div class="d-flex gap-2">
                    <x-primary-button>Create Institute Type</x-primary-button>
                    <a href="{{ route('super-admin.institute-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-super-admin-layout>
