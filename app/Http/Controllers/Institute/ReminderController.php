<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * A single "Send Reminder" module bringing together the two bulk-send
 * actions (Fee Dues / Absent Students) that used to be scattered as
 * per-row buttons on the Fees and Attendance pages. Each section is gated
 * independently by fees,view / attendance,view — same per-section split
 * precedent as the dashboard's stat cards — since a role might only have
 * one of the two permissions.
 */
class ReminderController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $canFees = $user->canModule('fees', 'view');
        $canAttendance = $user->canModule('attendance', 'view');

        abort_unless($canFees || $canAttendance, 403, 'You do not have permission to access this page.');

        $pendingFees = collect();
        if ($canFees) {
            $pendingFees = StudentFee::with(['student' => fn ($q) => $q->withTrashed()])
                ->get()
                ->filter(fn (StudentFee $fee) => $fee->pending_amount > 0)
                ->sortBy(fn (StudentFee $fee) => $fee->student?->name)
                ->values();
        }

        $attendanceDate = (string) $request->query('attendance_date', now()->toDateString());
        if ($attendanceDate > now()->toDateString()) {
            $attendanceDate = now()->toDateString();
        }

        $absentStudents = collect();
        if ($canAttendance) {
            $absentStudents = Attendance::with('student')
                ->where('attendance_date', $attendanceDate)
                ->where('status', 'absent')
                ->get()
                ->pluck('student')
                ->filter()
                ->sortBy('name')
                ->values();
        }

        return view('institute.reminders.index', [
            'canFees' => $canFees,
            'canAttendance' => $canAttendance,
            'pendingFees' => $pendingFees,
            'absentStudents' => $absentStudents,
            'attendanceDate' => $attendanceDate,
        ]);
    }
}
