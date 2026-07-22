<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StorePlanRequest;
use App\Http\Requests\SuperAdmin\UpdatePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderBy('price')->get();

        return view('super-admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('super-admin.plans.create');
    }

    public function store(StorePlanRequest $request): RedirectResponse
    {
        Plan::create($request->validated());

        return redirect()->route('super-admin.plans.index')->with('status', 'Plan created.');
    }

    public function edit(Plan $plan): View
    {
        return view('super-admin.plans.edit', compact('plan'));
    }

    public function update(UpdatePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return redirect()->route('super-admin.plans.index')->with('status', 'Plan updated.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $institutesUsingPlan = $plan->subscriptions()->with('institute')->get()->pluck('institute.name');

        if ($institutesUsingPlan->isNotEmpty()) {
            return back()->withErrors([
                'plan' => 'Cannot delete this plan — it is currently assigned to: '.$institutesUsingPlan->implode(', ').'. Deactivate it instead.',
            ]);
        }

        $plan->delete();

        return redirect()->route('super-admin.plans.index')->with('status', 'Plan deleted.');
    }

    public function toggleStatus(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('status', "Plan \"{$plan->name}\" is now ".($plan->is_active ? 'active.' : 'inactive.'));
    }
}
