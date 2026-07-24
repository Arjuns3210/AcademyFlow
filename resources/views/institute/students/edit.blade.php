<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Edit Student</h2>
            <div class="d-flex gap-2 align-items-center">
                <x-back-link :href="route('institute.students.index')" />
                @canModule('students', 'status')
                    @include('institute.students._status-form', ['student' => $student])
                @endcanModule
            </div>
        </div>
    </x-slot>

    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form action="{{ route('institute.students.update', $student) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('institute.students._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Save Changes</x-primary-button>
                    <a href="{{ route('institute.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
