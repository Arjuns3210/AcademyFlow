<x-super-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Activate / Renew Subscription — {{ $subscription->institute->name }}</h2>
            <x-back-link :href="route('super-admin.subscriptions.index')" />
        </div>
    </x-slot>

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="text-muted small">
                Current status:
                <span class="badge {{ match($subscription->status) { 'trial' => 'bg-warning text-dark', 'active' => 'bg-success', 'expired' => 'bg-danger', 'cancelled' => 'bg-secondary' } }}">
                    {{ ucfirst($subscription->status) }}
                </span>
                @if ($subscription->plan)
                    &middot; currently on {{ $subscription->plan->name }}
                @endif
            </p>

            <form action="{{ route('super-admin.subscriptions.update', $subscription) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <x-input-label for="plan_id" value="Plan" />
                    <select name="plan_id" id="plan_id" class="form-select" required>
                        <option value="">Select a plan&hellip;</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" data-billing-cycle="{{ $plan->billing_cycle }}"
                                {{ (int) old('plan_id', $subscription->plan_id ?? '') === $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — ₹{{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('plan_id')" />
                </div>

                <x-form-input name="coupon_code" label="Coupon Code (optional)" :value="old('coupon_code', '')" placeholder="e.g. SAVE20" />

                <div class="row">
                    <div class="col-md-6">
                        <x-form-input name="current_period_start" label="Period Start" type="date" :value="old('current_period_start', now()->toDateString())" required />
                    </div>
                    <div class="col-md-6">
                        <x-form-input name="current_period_end" label="Period End" type="date" :value="old('current_period_end', now()->addMonth()->toDateString())" required />
                    </div>
                </div>

                <p class="text-muted small">
                    Period End defaults to +1 month or +1 year based on the selected plan's billing cycle — adjust it manually if needed.
                </p>

                <div class="d-flex gap-2">
                    <x-primary-button>Activate / Renew</x-primary-button>
                    <a href="{{ route('super-admin.subscriptions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const planSelect = document.getElementById('plan_id');
            const startInput = document.getElementById('current_period_start');
            const endInput = document.getElementById('current_period_end');
            let endManuallyEdited = false;

            endInput.addEventListener('input', () => {
                endManuallyEdited = true;
            });

            function recomputeEnd() {
                if (endManuallyEdited) {
                    return;
                }

                const cycle = planSelect.selectedOptions[0]?.dataset.billingCycle;
                const start = new Date(startInput.value);

                if (!cycle || isNaN(start.getTime())) {
                    return;
                }

                const end = new Date(start);
                if (cycle === 'yearly') {
                    end.setFullYear(end.getFullYear() + 1);
                } else {
                    end.setMonth(end.getMonth() + 1);
                }

                endInput.value = end.toISOString().slice(0, 10);
            }

            planSelect.addEventListener('change', recomputeEnd);
            startInput.addEventListener('change', recomputeEnd);
            recomputeEnd();
        })();
    </script>
</x-super-admin-layout>
