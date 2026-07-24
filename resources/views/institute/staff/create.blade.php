<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">New Staff Member</h2>
            <x-back-link :href="route('institute.staff.index')" />
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form action="{{ route('institute.staff.store') }}" method="POST">
                @csrf
                @include('institute.staff._form')

                <div class="d-flex gap-2">
                    <x-primary-button>Create Staff Member</x-primary-button>
                    <a href="{{ route('institute.staff.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-tenant-layout>
