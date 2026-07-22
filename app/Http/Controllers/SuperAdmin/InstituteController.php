<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\StoreInstituteRequest;
use App\Http\Requests\SuperAdmin\UpdateInstituteRequest;
use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\Module;
use App\Models\User;
use App\Services\InstituteProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstituteController extends Controller
{
    public function __construct(private InstituteProvisioningService $provisioning) {}

    public function index(): View
    {
        $institutes = Institute::with('instituteType')
            ->withCount(['instituteModules as enabled_modules_count' => fn ($q) => $q->where('is_enabled', true)])
            ->orderByDesc('created_at')
            ->get();

        return view('super-admin.institutes.index', compact('institutes'));
    }

    public function create(): View
    {
        [$instituteTypes, $modules, $typeModuleDefaults] = $this->formLookups();

        return view('super-admin.institutes.create', compact('instituteTypes', 'modules', 'typeModuleDefaults'));
    }

    public function store(StoreInstituteRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $institute = DB::transaction(function () use ($request, $data) {
            $institute = Institute::create([
                'institute_type_id' => $data['institute_type_id'],
                'name' => $data['name'],
                'logo' => $request->hasFile('logo') ? $request->file('logo')->store('institute-logos', 'public') : null,
                'theme_color' => $data['theme_color'] ?? null,
                'address' => $data['address'] ?? null,
                'contact' => $data['contact'] ?? null,
                'email' => $data['email'] ?? null,
                'gst_number' => $data['gst_number'] ?? null,
                'status' => 'active',
            ]);

            $this->syncModules($institute, $data);

            $adminRole = $this->provisioning->createDefaultAdminRole($institute);

            User::create([
                'tenant_id' => $institute->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'role' => RoleEnum::TenantUser,
                'role_id' => $adminRole->id,
                'email_verified_at' => now(),
            ]);

            $this->provisioning->startTrialSubscription($institute);

            return $institute;
        });

        return redirect()->route('super-admin.institutes.index')
            ->with('status', "Institute \"{$institute->name}\" created. Admin login: {$data['admin_email']}");
    }

    public function show(Institute $institute): View
    {
        $institute->load('instituteType', 'modules');

        return view('super-admin.institutes.show', [
            'institute' => $institute,
            'admin' => $institute->adminUser(),
        ]);
    }

    public function edit(Institute $institute): View
    {
        [$instituteTypes, $modules, $typeModuleDefaults] = $this->formLookups();

        $institute->load('modules');
        $selectedModuleIds = $institute->modules->where('pivot.is_enabled', true)->pluck('id')->all();
        $customLabels = $institute->modules->pluck('pivot.custom_label', 'id')->all();

        return view('super-admin.institutes.edit', compact(
            'institute', 'instituteTypes', 'modules', 'typeModuleDefaults', 'selectedModuleIds', 'customLabels'
        ) + ['admin' => $institute->adminUser()]);
    }

    public function update(UpdateInstituteRequest $request, Institute $institute): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $institute) {
            $logoPath = $institute->logo;

            if ($request->hasFile('logo')) {
                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }

                $logoPath = $request->file('logo')->store('institute-logos', 'public');
            }

            $institute->update([
                'institute_type_id' => $data['institute_type_id'],
                'name' => $data['name'],
                'logo' => $logoPath,
                'theme_color' => $data['theme_color'] ?? null,
                'address' => $data['address'] ?? null,
                'contact' => $data['contact'] ?? null,
                'email' => $data['email'] ?? null,
                'gst_number' => $data['gst_number'] ?? null,
            ]);

            $this->syncModules($institute, $data);

            // Keep the Institute Admin role's permissions in sync with
            // whatever's enabled now. Existing permission rows for modules
            // that just got disabled are left as-is (harmless — PermissionService
            // gates on institute_modules.is_enabled before ever consulting
            // role_permissions, so a stale can_view=true on a disabled module
            // is inert, not a leak).
            $this->provisioning->createDefaultAdminRole($institute);
        });

        return redirect()->route('super-admin.institutes.index')->with('status', "Institute \"{$institute->name}\" updated.");
    }

    public function toggleStatus(Institute $institute): RedirectResponse
    {
        $institute->update(['status' => $institute->isActive() ? 'inactive' : 'active']);

        ActivityLog::record(
            $institute->isActive() ? 'institute_unblocked' : 'institute_blocked',
            $institute,
            "Institute \"{$institute->name}\" was ".($institute->isActive() ? 'unblocked' : 'blocked')." by Super Admin.",
            $institute->id
        );

        return back()->with('status', "Institute \"{$institute->name}\" is now ".($institute->isActive() ? 'active.' : 'blocked.'));
    }

    public function resetAdminPassword(Institute $institute): RedirectResponse
    {
        $admin = $institute->adminUser();

        abort_if(! $admin, 404, 'This institute has no Institute Admin user yet.');

        $newPassword = Str::password(12);

        $admin->update(['password' => $newPassword]);

        return redirect()->route('super-admin.institutes.edit', $institute)
            ->with('newAdminPassword', $newPassword);
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection, 2: \Illuminate\Support\Collection}
     */
    private function formLookups(): array
    {
        $instituteTypes = InstituteType::with('modules')->orderBy('name')->get();
        $modules = Module::where('is_active', true)->orderBy('name')->get();

        $typeModuleDefaults = $instituteTypes->mapWithKeys(fn ($type) => [
            $type->id => $type->modules->pluck('id'),
        ]);

        return [$instituteTypes, $modules, $typeModuleDefaults];
    }

    private function syncModules(Institute $institute, array $data): void
    {
        $selectedModuleIds = collect($data['modules'] ?? [])->map(fn ($id) => (int) $id)->all();
        $customLabels = $data['custom_labels'] ?? [];

        foreach ($selectedModuleIds as $moduleId) {
            $institute->instituteModules()->updateOrCreate(
                ['module_id' => $moduleId],
                ['custom_label' => $customLabels[$moduleId] ?? null, 'is_enabled' => true]
            );
        }

        $institute->instituteModules()
            ->whereNotIn('module_id', $selectedModuleIds ?: [0])
            ->update(['is_enabled' => false]);
    }
}
