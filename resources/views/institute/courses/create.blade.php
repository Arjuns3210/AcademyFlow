<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New {{ Str::singular($moduleLabel) }}</h2>
            <x-back-link :href="route('institute.courses.index')" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form action="{{ route('institute.courses.store') }}" method="POST">
                @csrf
                @include('institute.courses._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Save</x-primary-button>
                    <a href="{{ route('institute.courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
