<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreInstituteTypeRequest;
use App\Http\Requests\SuperAdmin\UpdateInstituteTypeRequest;
use App\Models\InstituteType;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InstituteTypeController extends Controller
{
    public function index(): View
    {
        $instituteTypes = InstituteType::withCount('institutes')->orderBy('name')->get();

        return view('super-admin.institute-types.index', compact('instituteTypes'));
    }

    public function create(): View
    {
        $modules = Module::where('is_active', true)->orderBy('name')->get();

        return view('super-admin.institute-types.create', compact('modules'));
    }

    public function store(StoreInstituteTypeRequest $request): RedirectResponse
    {
        $instituteType = InstituteType::create($request->safe()->only('name'));

        $instituteType->modules()->sync($request->input('modules', []));

        return redirect()->route('super-admin.institute-types.index')->with('status', 'Institute type created.');
    }

    public function edit(InstituteType $instituteType): View
    {
        $modules = Module::where('is_active', true)->orderBy('name')->get();
        $selectedModuleIds = $instituteType->modules()->pluck('modules.id')->all();

        return view('super-admin.institute-types.edit', compact('instituteType', 'modules', 'selectedModuleIds'));
    }

    public function update(UpdateInstituteTypeRequest $request, InstituteType $instituteType): RedirectResponse
    {
        $instituteType->update($request->safe()->only('name'));

        $instituteType->modules()->sync($request->input('modules', []));

        return redirect()->route('super-admin.institute-types.index')->with('status', 'Institute type updated.');
    }

    public function destroy(InstituteType $instituteType): RedirectResponse
    {
        $institutesUsingType = $instituteType->institutes()->pluck('name');

        if ($institutesUsingType->isNotEmpty()) {
            return back()->withErrors([
                'institute_type' => 'Cannot delete this institute type — it is currently used by: '.$institutesUsingType->implode(', ').'.',
            ]);
        }

        $instituteType->delete();

        return redirect()->route('super-admin.institute-types.index')->with('status', 'Institute type deleted.');
    }
}
