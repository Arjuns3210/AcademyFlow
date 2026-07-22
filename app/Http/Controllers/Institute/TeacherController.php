<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreTeacherRequest;
use App\Http\Requests\Institute\UpdateTeacherRequest;
use App\Http\Requests\Institute\UpdateTeacherStatusRequest;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Teacher::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $teachers = $query->with('user')->orderBy('name')->paginate(20)->withQueryString();

        return view('institute.teachers.index', [
            'teachers' => $teachers,
            'search' => $search,
            'moduleLabel' => Auth::user()->institute->moduleLabel('teachers'),
        ]);
    }

    public function create(): View
    {
        return view('institute.teachers.create', [
            'linkableUsers' => $this->linkableUsers(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('teachers'),
        ]);
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Teacher::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $data['name'],
            'qualification' => $data['qualification'] ?? null,
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'subjects' => $data['subjects'] ?? null,
            'photo' => $request->hasFile('photo') ? $request->file('photo')->store('teacher-photos', 'public') : null,
            'user_id' => $data['user_id'] ?? null,
        ]);

        return redirect()->route('institute.teachers.index')->with('status', 'Teacher added.');
    }

    public function edit(Teacher $teacher): View
    {
        return view('institute.teachers.edit', [
            'teacher' => $teacher,
            'linkableUsers' => $this->linkableUsers(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('teachers'),
        ]);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): RedirectResponse
    {
        $data = $request->validated();

        $photoPath = $teacher->photo;

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            $photoPath = $request->file('photo')->store('teacher-photos', 'public');
        }

        $teacher->update([
            'name' => $data['name'],
            'qualification' => $data['qualification'] ?? null,
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'subjects' => $data['subjects'] ?? null,
            'photo' => $photoPath,
            'user_id' => $data['user_id'] ?? null,
        ]);

        return redirect()->route('institute.teachers.index')->with('status', 'Teacher updated.');
    }

    public function destroy(Teacher $teacher): RedirectResponse
    {
        $batchesCount = $teacher->batches()->count();

        if ($batchesCount > 0) {
            return back()->withErrors([
                'teacher' => "Cannot delete \"{$teacher->name}\" — currently assigned to {$batchesCount} batch(es). Unassign or deactivate instead.",
            ]);
        }

        $teacher->delete();

        return redirect()->route('institute.teachers.index')->with('status', "\"{$teacher->name}\" deleted.");
    }

    public function updateStatus(UpdateTeacherStatusRequest $request, Teacher $teacher): RedirectResponse
    {
        $teacher->update(['status' => $request->validated('status')]);

        return back()->with('status', "\"{$teacher->name}\" status updated to \"{$request->validated('status')}\".");
    }

    /**
     * This institute's users, for the optional "Link to staff login"
     * dropdown — shown as-is per Sprint 3 scope; a user already linked to
     * another teacher is still listed but will be rejected by validation
     * if picked, rather than filtered out of the list here.
     */
    private function linkableUsers()
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();
    }
}
