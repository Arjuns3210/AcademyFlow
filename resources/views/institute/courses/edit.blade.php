<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Edit {{ Str::singular($moduleLabel) }}</h2>
            <div class="d-flex gap-2 align-items-center">
                <x-back-link :href="route('institute.courses.index')" :label="$moduleLabel" />
                @canModule('courses', 'status')
                    @include('institute.courses._status-form', ['course' => $course])
                @endcanModule
            </div>
        </div>
    </x-slot>

    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form action="{{ route('institute.courses.update', $course) }}" method="POST">
                @csrf
                @method('PUT')
                @include('institute.courses._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('institute.courses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
