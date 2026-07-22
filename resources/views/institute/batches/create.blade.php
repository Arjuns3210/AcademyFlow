<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New {{ Str::singular($moduleLabel) }}</h2>
            <x-back-link :href="route('institute.batches.index')" :label="$moduleLabel" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 800px;">
        <div class="card-body">
            <form action="{{ route('institute.batches.store') }}" method="POST">
                @csrf
                @include('institute.batches._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Save</x-primary-button>
                    <a href="{{ route('institute.batches.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
