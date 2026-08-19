<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\WhatsAppLog;
use Illuminate\Http\JsonResponse;
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
            'attendanceDateFormatted' => \Illuminate\Support\Carbon::parse($attendanceDate)->format('d M Y'),
            'instituteName' => $user->institute?->name ?? '',
        ]);
    }

    /**
     * Records that staff opened a wa.me link for a student's pending fee —
     * not that WhatsApp actually delivered anything, since sending happens
     * in the staff member's own WhatsApp app after this. Reuses whatsapp_logs
     * (source = manual_link, status = link_opened) rather than a parallel
     * table, so both channels show up in one audit trail.
     */
    public function logManualWhatsApp(Request $request, StudentFee $studentFee): JsonResponse
    {
        abort_unless(Auth::user()->canModule('fees', 'view'), 403);

        $student = $studentFee->student;
        abort_if(! $student, 404);

        return $this->recordManualWhatsAppLog($request, $student, 'manual_fee_reminder');
    }

    /**
     * Same manual wa.me audit trail as logManualWhatsApp() above, for the
     * Attendance Reminders tab instead of Fee Reminders — kept as a
     * separate action (rather than one route branching on a "kind" param)
     * so it can be gated by attendance,view independently of fees,view,
     * matching the existing per-section permission split on this page.
     */
    public function logManualWhatsAppAttendance(Request $request, Student $student): JsonResponse
    {
        abort_unless(Auth::user()->canModule('attendance', 'view'), 403);

        return $this->recordManualWhatsAppLog($request, $student, 'manual_attendance_reminder');
    }

    private function recordManualWhatsAppLog(Request $request, Student $student, string $templateName): JsonResponse
    {
        $data = $request->validate([
            'recipient_number' => ['required', 'string', 'max:20'],
        ]);

        WhatsAppLog::create([
            'institute_id' => Auth::user()->tenant_id,
            'source' => 'manual_link',
            'student_id' => $student->id,
            'template_name' => $templateName,
            'recipient_number' => $data['recipient_number'],
            'status' => 'link_opened',
        ]);

        return response()->json(['status' => 'ok']);
    }
}
