<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Services\ModuleAccessService;
use App\Support\PlanModuleKeys;
use Illuminate\View\View;

class FeatureLockedController extends Controller
{
    public function show(string $moduleKey, ModuleAccessService $moduleAccess): View
    {
        return view('institute.feature-locked', [
            'moduleKey' => $moduleKey,
            'moduleLabel' => PlanModuleKeys::label($moduleKey),
            'unlockingPlans' => $moduleAccess->plansWithAccess($moduleKey),
        ]);
    }
}
