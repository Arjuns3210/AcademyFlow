<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreCourseRequest;
use App\Http\Requests\Institute\UpdateCourseRequest;
use App\Http\Requests\Institute\UpdateCourseStatusRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $query = Course::query();

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $courses = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('institute.courses.index', [
            'courses' => $courses,
            'search' => $search,
            'moduleLabel' => Auth::user()->institute->moduleLabel('courses'),
        ]);
    }

    public function create(): View
    {
        return view('institute.courses.create', [
            'moduleLabel' => Auth::user()->institute->moduleLabel('courses'),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Course::create([
            'tenant_id' => Auth::user()->tenant_id,
            ...$request->validated(),
        ]);

        return redirect()->route('institute.courses.index')->with('status', 'Course created.');
    }

    public function edit(Course $course): View
    {
        return view('institute.courses.edit', [
            'course' => $course,
            'moduleLabel' => Auth::user()->institute->moduleLabel('courses'),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('institute.courses.index')->with('status', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $activeStudentsCount = $course->students()->where('status', 'active')->count();

        if ($activeStudentsCount > 0) {
            return back()->withErrors([
                'course' => "Cannot delete \"{$course->name}\" — it is currently assigned to {$activeStudentsCount} active student(s). Deactivate it instead.",
            ]);
        }

        $course->delete();

        return redirect()->route('institute.courses.index')->with('status', "\"{$course->name}\" deleted.");
    }

    public function updateStatus(UpdateCourseStatusRequest $request, Course $course): RedirectResponse
    {
        $course->update(['status' => $request->validated('status')]);

        return back()->with('status', "\"{$course->name}\" status updated to \"{$request->validated('status')}\".");
    }
}
