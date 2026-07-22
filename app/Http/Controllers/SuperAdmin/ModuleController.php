<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreModuleRequest;
use App\Http\Requests\SuperAdmin\UpdateModuleRequest;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = Module::orderBy('name')->get();

        return view('super-admin.modules.index', compact('modules'));
    }

    public function create(): View
    {
        return view('super-admin.modules.create');
    }

    public function store(StoreModuleRequest $request): RedirectResponse
    {
        Module::create($request->validated());

        return redirect()->route('super-admin.modules.index')->with('status', 'Module created.');
    }

    public function edit(Module $module): View
    {
        return view('super-admin.modules.edit', compact('module'));
    }

    public function update(UpdateModuleRequest $request, Module $module): RedirectResponse
    {
        $module->update($request->validated());

        return redirect()->route('super-admin.modules.index')->with('status', 'Module updated.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $institutesUsingModule = $module->institutes()
            ->wherePivot('is_enabled', true)
            ->pluck('institutes.name');

        if ($institutesUsingModule->isNotEmpty()) {
            return back()->withErrors([
                'module' => 'Cannot delete this module — it is currently enabled for: '.$institutesUsingModule->implode(', ').'.',
            ]);
        }

        $module->delete();

        return redirect()->route('super-admin.modules.index')->with('status', 'Module deleted.');
    }

    public function toggleStatus(Module $module): RedirectResponse
    {
        $module->update(['is_active' => ! $module->is_active]);

        return back()->with('status', "Module \"{$module->name}\" is now ".($module->is_active ? 'active.' : 'inactive.'));
    }
}
