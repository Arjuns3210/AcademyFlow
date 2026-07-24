<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Edit Institute Type</h2>
            <x-back-link :href="route('super-admin.institute-types.index')" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 720px;">
        <div class="card-body">
            <form action="{{ route('super-admin.institute-types.update', $instituteType) }}" method="POST">
                @csrf
                @method('PUT')
                @include('super-admin.institute-types._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('super-admin.institute-types.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-super-admin-layout>
