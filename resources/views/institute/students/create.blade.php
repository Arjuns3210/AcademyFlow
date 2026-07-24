<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Student</h2>
            <x-back-link :href="route('institute.students.index')" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 900px;">
        <div class="card-body">
            <form action="{{ route('institute.students.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('institute.students._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Admit Student</x-primary-button>
                    <a href="{{ route('institute.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
