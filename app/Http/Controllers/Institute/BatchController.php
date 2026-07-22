<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreBatchRequest;
use App\Http\Requests\Institute\UpdateBatchRequest;
use App\Http\Requests\Institute\UpdateBatchStatusRequest;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(): View
    {
        $batches = Batch::with(['course', 'teacher'])->orderBy('name')->paginate(20);

        return view('institute.batches.index', [
            'batches' => $batches,
            'moduleLabel' => Auth::user()->institute->moduleLabel('batches'),
        ]);
    }

    public function create(): View
    {
        return view('institute.batches.create', [
            'courses' => $this->selectableCourses(),
            'teachers' => $this->selectableTeachers(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('batches'),
        ]);
    }

    public function store(StoreBatchRequest $request): RedirectResponse
    {
        Batch::create([
            'tenant_id' => Auth::user()->tenant_id,
            ...$request->validated(),
        ]);

        return redirect()->route('institute.batches.index')->with('status', 'Batch created.');
    }

    public function edit(Batch $batch): View
    {
        return view('institute.batches.edit', [
            'batch' => $batch,
            'courses' => $this->selectableCourses($batch),
            'teachers' => $this->selectableTeachers($batch),
            'moduleLabel' => Auth::user()->institute->moduleLabel('batches'),
        ]);
    }

    public function update(UpdateBatchRequest $request, Batch $batch): RedirectResponse
    {
        $batch->update($request->validated());

        return redirect()->route('institute.batches.index')->with('status', 'Batch updated.');
    }

    public function destroy(Batch $batch): RedirectResponse
    {
        $studentsCount = $batch->students()->count();

        if ($studentsCount > 0) {
            return back()->withErrors([
                'batch' => "Cannot delete \"{$batch->name}\" — it currently has {$studentsCount} student(s) assigned. Deactivate it instead.",
            ]);
        }

        $batch->delete();

        return redirect()->route('institute.batches.index')->with('status', "\"{$batch->name}\" deleted.");
    }

    public function updateStatus(UpdateBatchStatusRequest $request, Batch $batch): RedirectResponse
    {
        $batch->update(['status' => $request->validated('status')]);

        return back()->with('status', "\"{$batch->name}\" status updated to \"{$request->validated('status')}\".");
    }

    /**
     * Active courses, plus the batch's currently assigned course even if it
     * has since been deactivated — so editing a batch never silently drops
     * or changes a selection the form didn't touch.
     */
    private function selectableCourses(?Batch $batch = null): Collection
    {
        return Course::where(function ($query) use ($batch) {
            $query->where('status', 'active');

            if ($batch?->course_id) {
                $query->orWhere('id', $batch->course_id);
            }
        })->orderBy('name')->get();
    }

    /**
     * Same "active, or currently assigned" rule as selectableCourses().
     */
    private function selectableTeachers(?Batch $batch = null): Collection
    {
        return Teacher::where(function ($query) use ($batch) {
            $query->where('status', 'active');

            if ($batch?->teacher_id) {
                $query->orWhere('id', $batch->teacher_id);
            }
        })->orderBy('name')->get();
    }
}
