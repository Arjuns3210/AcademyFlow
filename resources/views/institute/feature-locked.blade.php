<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">{{ $moduleLabel }}</h2>
            <x-back-link :href="route('dashboard')" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 600px;">
        <div class="card-body text-center p-5">
            <div class="display-4 text-muted mb-3">
                <i class="bi bi-lock"></i>
            </div>
            <h3 class="h5 mb-3">This feature isn't included in your current plan</h3>
            <p class="text-muted mb-4">
                {{ $moduleLabel }} is available on
                @if (count($unlockingPlans) > 0)
                    the {{ implode(' or ', $unlockingPlans) }} plan{{ count($unlockingPlans) > 1 ? 's' : '' }}.
                @else
                    a higher plan.
                @endif
                Contact us to upgrade and unlock it for your institute.
            </p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</x-tenant-layout>
