<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\ActivateSubscriptionRequest;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(): View
    {
        $subscriptions = Subscription::with(['institute', 'plan'])
            ->get()
            ->sortBy(fn (Subscription $subscription) => $subscription->institute->name)
            ->values();

        return view('super-admin.subscriptions.index', compact('subscriptions'));
    }

    public function edit(Subscription $subscription): View
    {
        $subscription->load('institute', 'plan');

        // Plans deactivated (not deleted) fall out of this list on purpose —
        // deactivating one is exactly meant to stop it being newly assigned,
        // same as Plan's own delete-guard prefers deactivate over delete.
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return view('super-admin.subscriptions.edit', compact('subscription', 'plans'));
    }

    /**
     * Same form handles activating a fresh trial, renewing an active
     * subscription, or reactivating an expired/cancelled one — the spec
     * treats all of those as one "Activate/Renew" action, not separate
     * flows per status.
     */
    public function update(ActivateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validated();
        $plan = Plan::findOrFail($data['plan_id']);
        $coupon = $request->resolvedCoupon();

        $amountPaid = $coupon
            ? max(0, $this->discountedAmount($plan->price, $coupon))
            : (float) $plan->price;

        DB::transaction(function () use ($subscription, $plan, $coupon, $data, $amountPaid) {
            if ($coupon) {
                $coupon->increment('times_used');
            }

            $subscription->update([
                'plan_id' => $plan->id,
                'coupon_id' => $coupon?->id,
                'status' => 'active',
                'current_period_start' => $data['current_period_start'],
                'current_period_end' => $data['current_period_end'],
                'amount_paid' => round($amountPaid, 2),
            ]);

            ActivityLog::record(
                'subscription_activated',
                $subscription,
                "Subscription activated on the {$plan->name} plan (₹".number_format(round($amountPaid, 2), 2).') by Super Admin.',
                $subscription->institute_id
            );
        });

        return redirect()->route('super-admin.subscriptions.index')
            ->with('status', "Subscription activated for {$subscription->institute->name}.");
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'cancelled']);

        ActivityLog::record(
            'subscription_cancelled',
            $subscription,
            'Subscription cancelled by Super Admin.',
            $subscription->institute_id
        );

        return back()->with('status', "Subscription for {$subscription->institute->name} has been cancelled.");
    }

    private function discountedAmount(string $planPrice, Coupon $coupon): float
    {
        return $coupon->discount_type === 'percentage'
            ? (float) $planPrice * (1 - (float) $coupon->discount_value / 100)
            : (float) $planPrice - (float) $coupon->discount_value;
    }
}
