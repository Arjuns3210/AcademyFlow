<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Plan</h2>
            <x-back-link :href="route('super-admin.plans.index')" label="Plans" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form action="{{ route('super-admin.plans.store') }}" method="POST">
                @csrf
                @include('super-admin.plans._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Create Plan</x-primary-button>
                    <a href="{{ route('super-admin.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-super-admin-layout>
