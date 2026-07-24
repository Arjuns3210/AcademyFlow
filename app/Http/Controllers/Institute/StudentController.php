<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreStudentRequest;
use App\Http\Requests\Institute\UpdateStudentRequest;
use App\Http\Requests\Institute\UpdateStudentStatusRequest;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $showTrashed = $request->boolean('trashed');
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');
        $courseFilter = (string) $request->query('course_id', '');
        $batchFilter = (string) $request->query('batch_id', '');

        $query = $showTrashed ? Student::onlyTrashed() : Student::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%")
                    ->orWhere('parent_mobile', 'like', "%{$search}%")
                    ->orWhere('student_mobile', 'like', "%{$search}%");
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($courseFilter !== '') {
            $query->where('course_id', $courseFilter);
        }

        if ($batchFilter !== '') {
            $query->where('batch_id', $batchFilter);
        }

        $students = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('institute.students.index', [
            'students' => $students,
            'showTrashed' => $showTrashed,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'courseFilter' => $courseFilter,
            'batchFilter' => $batchFilter,
            // Any course/batch that's ever been used, not just active ones —
            // filtering existing data shouldn't hide options tied to records
            // that already exist.
            'courses' => Course::orderBy('name')->get(),
            'batches' => Batch::orderBy('name')->get(),
        ]);
    }

    public function show(Student $student): View
    {
        // Gated independently of students,view — a role without
        // attendance,view shouldn't see attendance data just because it can
        // view student profiles for other reasons (mirrors how Fees stayed
        // decoupled from Students permissions in Sprint 4).
        $attendanceSummary = Auth::user()->canModule('attendance', 'view')
            ? Attendance::monthlySummaryFor($student->id)
            : null;

        // Same independent gating, same reasoning, applied to fees.
        $feeSummary = Auth::user()->canModule('fees', 'view') ? $student->fee : null;
        $recentTransactions = $feeSummary
            ? $feeSummary->transactions()->orderByDesc('payment_date')->orderByDesc('id')->limit(5)->get()
            : null;

        return view('institute.students.show', [
            'student' => $student,
            'attendanceSummary' => $attendanceSummary,
            'feeSummary' => $feeSummary,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    public function create(): View
    {
        $selectedCourseId = (int) old('course_id') ?: null;

        return view('institute.students.create', [
            'courses' => $this->selectableCourses(),
            'batches' => $this->selectableBatches($selectedCourseId),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Student::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $data['name'],
            'photo' => $request->hasFile('photo') ? $request->file('photo')->store('student-photos', 'public') : null,
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_mobile' => $data['student_mobile'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'parent_mobile' => $data['parent_mobile'],
            'parent_email' => $data['parent_email'] ?? null,
            'address' => $data['address'] ?? null,
            'admission_date' => $data['admission_date'] ?? now()->toDateString(),
            'course_id' => $data['course_id'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
        ]);

        return redirect()->route('institute.students.index')->with('status', 'Student admitted successfully.');
    }

    public function edit(Student $student): View
    {
        $selectedCourseId = (int) old('course_id', $student->course_id) ?: null;

        return view('institute.students.edit', [
            'student' => $student,
            'courses' => $this->selectableCourses($student->course_id),
            'batches' => $this->selectableBatches($selectedCourseId, $student->batch_id),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        $photoPath = $student->photo;

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            $photoPath = $request->file('photo')->store('student-photos', 'public');
        }

        $student->update([
            'name' => $data['name'],
            'photo' => $photoPath,
            'dob' => $data['dob'] ?? null,
            'gender' => $data['gender'] ?? null,
            'student_mobile' => $data['student_mobile'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'parent_mobile' => $data['parent_mobile'],
            'parent_email' => $data['parent_email'] ?? null,
            'address' => $data['address'] ?? null,
            'admission_date' => $data['admission_date'] ?? $student->admission_date,
            'course_id' => $data['course_id'] ?? null,
            'batch_id' => $data['batch_id'] ?? null,
        ]);

        return redirect()->route('institute.students.index')->with('status', 'Student updated.');
    }

    /**
     * AJAX: active batches for a course, refreshed when the create/edit
     * form's course dropdown changes. Excludes tenant/permission concerns
     * beyond the route's own gating — Course's route-model binding already
     * 404s a cross-tenant course the same way show/edit do.
     */
    public function batchesForCourse(Course $course): JsonResponse
    {
        $batches = $course->batches()->where('status', 'active')->orderBy('name')->get();

        return response()->json($batches->map(fn (Batch $batch) => [
            'id' => $batch->id,
            'name' => $batch->name,
            'time_slot' => $batch->time_slot,
            'enrolled' => $batch->enrolledCount(),
            'capacity' => $batch->capacity,
            'is_full' => $batch->isFull(),
        ])->values());
    }

    /**
     * Active courses, plus a specific course even if it's since been
     * deactivated — so the edit form never silently drops the student's
     * existing course selection.
     */
    private function selectableCourses(?int $currentCourseId = null): Collection
    {
        return Course::where(function ($query) use ($currentCourseId) {
            $query->where('status', 'active');

            if ($currentCourseId) {
                $query->orWhere('id', $currentCourseId);
            }
        })->orderBy('name')->get();
    }

    /**
     * Active batches for a given course, plus a specific batch even if it's
     * since been deactivated/filled — same reasoning as selectableCourses().
     * Empty when no course is selected yet.
     */
    private function selectableBatches(?int $courseId, ?int $currentBatchId = null): Collection
    {
        if (! $courseId) {
            return new Collection();
        }

        return Batch::where('course_id', $courseId)
            ->where(function ($query) use ($currentBatchId) {
                $query->where('status', 'active');

                if ($currentBatchId) {
                    $query->orWhere('id', $currentBatchId);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();

        return redirect()->route('institute.students.index')->with('status', "\"{$student->name}\" moved to trash.");
    }

    public function updateStatus(UpdateStudentStatusRequest $request, Student $student): RedirectResponse
    {
        $student->update(['status' => $request->validated('status')]);

        return back()->with('status', "\"{$student->name}\" status updated to \"{$request->validated('status')}\".");
    }
}
