<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $date = $this->clampToToday($request->query('date', now()->toDateString()));
        $batchId = (string) $request->query('batch_id', '');

        [$students, $existingMarks] = $this->studentsAndMarksForDate($date, $batchId);

        $isToday = $date === now()->toDateString();
        $requiredAction = $isToday ? 'add' : 'edit';

        return view('institute.attendance.index', [
            'students' => $students,
            'existingMarks' => $existingMarks,
            'date' => $date,
            'batchId' => $batchId,
            'batches' => Batch::orderBy('name')->get(),
            'isToday' => $isToday,
            'canSubmit' => Auth::user()->canModule('attendance', $requiredAction),
            'markedCount' => $existingMarks->count(),
            'totalCount' => $students->count(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('attendance'),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            foreach ($data['statuses'] as $studentId => $status) {
                // findOrFail (not just trusting the submitted ID) both
                // confirms the student exists and — via the tenant global
                // scope — that they belong to this tenant, before their
                // batch_id is used to stamp the attendance row.
                $student = Student::findOrFail($studentId);

                Attendance::mark(
                    Auth::user()->tenant_id,
                    $student->id,
                    $student->batch_id,
                    $data['attendance_date'],
                    $status,
                    Auth::id(),
                );
            }
        });

        return redirect()->route('institute.attendance.index', [
            'date' => $data['attendance_date'],
            'batch_id' => $data['batch_id'] ?? null,
        ])->with('status', 'Attendance saved for '.Carbon::parse($data['attendance_date'])->format('d M Y').'.');
    }

    /**
     * Read-only — corrections happen back on the daily marking page (index)
     * by picking that specific past date, not inline here.
     */
    public function monthly(Request $request): View
    {
        [$year, $month] = $this->parseYearMonth($request->query('ym'));
        $studentId = (string) $request->query('student_id', '');
        $batchId = (string) $request->query('batch_id', '');

        $students = Student::activeFilteredBy($studentId, $batchId);
        [$rows, $daysInMonth] = $this->monthlyRowsFor($students, $year, $month);

        return view('institute.attendance.monthly', [
            'rows' => $rows,
            'daysInMonth' => $daysInMonth,
            'month' => $month,
            'year' => $year,
            'studentId' => $studentId,
            'batchId' => $batchId,
            'allStudents' => Student::where('status', 'active')->orderBy('name')->get(),
            'batches' => Batch::orderBy('name')->get(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('attendance'),
        ]);
    }

    /**
     * CSV rather than a real .xlsx (maatwebsite/excel) — opens fine in
     * Excel/Sheets/Numbers like any spreadsheet export, and the spec
     * explicitly allowed it as the v1 choice; no new dependency needed.
     */
    public function exportDaily(Request $request): StreamedResponse
    {
        $date = $this->clampToToday($request->query('date', now()->toDateString()));
        $batchId = (string) $request->query('batch_id', '');

        [$students, $marks] = $this->studentsAndMarksForDate($date, $batchId);

        return response()->streamDownload(function () use ($students, $marks) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Student Name', 'Student Code', 'Batch', 'Status']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student->name,
                    $student->student_code,
                    $student->batch?->name ?? '—',
                    ucfirst($marks->get($student->id)?->status ?? 'Not marked'),
                ]);
            }

            fclose($handle);
        }, "attendance-{$date}.csv", ['Content-Type' => 'text/csv']);
    }

    public function exportMonthly(Request $request): StreamedResponse
    {
        [$year, $month] = $this->parseYearMonth($request->query('ym'));
        $studentId = (string) $request->query('student_id', '');
        $batchId = (string) $request->query('batch_id', '');

        $students = Student::activeFilteredBy($studentId, $batchId);
        [$rows, $daysInMonth] = $this->monthlyRowsFor($students, $year, $month);

        return response()->streamDownload(function () use ($rows, $daysInMonth) {
            $handle = fopen('php://output', 'w');

            $header = ['Student Name', 'Student Code'];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $header[] = (string) $day;
            }
            $header = [...$header, 'Present', 'Absent', 'Late', 'Leave', '% Attendance'];
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                $line = [$row['student']->name, $row['student']->student_code];

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $line[] = match ($row['days'][$day]) {
                        'present' => 'P', 'absent' => 'A', 'late' => 'L', 'leave' => 'Lv', default => '',
                    };
                }

                $line = [
                    ...$line,
                    $row['summary']['present'],
                    $row['summary']['absent'],
                    $row['summary']['late'],
                    $row['summary']['leave'],
                    $row['percentage'] !== null ? $row['percentage'].'%' : '—',
                ];

                fputcsv($handle, $line);
            }

            fclose($handle);
        }, sprintf('attendance-monthly-%04d-%02d.csv', $year, $month), ['Content-Type' => 'text/csv']);
    }

    private function clampToToday(string $date): string
    {
        return $date > now()->toDateString() ? now()->toDateString() : $date;
    }

    /**
     * @return array{0: Collection, 1: Collection} [students, marksByStudentId]
     */
    private function studentsAndMarksForDate(string $date, string $batchId): array
    {
        $studentsQuery = Student::where('status', 'active');

        if ($batchId !== '') {
            $studentsQuery->where('batch_id', $batchId);
        }

        $students = $studentsQuery->orderBy('name')->get();

        $marks = Attendance::where('attendance_date', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        return [$students, $marks];
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: int} [rows, daysInMonth]
     */
    private function monthlyRowsFor(Collection $students, int $year, int $month): array
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;

        $recordsByStudent = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $startOfMonth->copy()->endOfMonth()->toDateString()])
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function (Student $student) use ($recordsByStudent, $daysInMonth) {
            $studentRecords = ($recordsByStudent->get($student->id) ?? collect())->keyBy(fn ($record) => $record->attendance_date->day);

            $days = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $days[$day] = $studentRecords->get($day)?->status;
            }

            // Same formula as the Student detail page's compact summary
            // (Attendance::monthlySummaryFor()) — this just avoids a second
            // query per student since these records are already fetched in
            // bulk above.
            $summary = Attendance::summarize($studentRecords);
            $percentage = $summary['percentage'];
            unset($summary['percentage']);

            return [
                'student' => $student,
                'days' => $days,
                'summary' => $summary,
                'percentage' => $percentage,
            ];
        });

        return [$rows, $daysInMonth];
    }

    /**
     * @return array{0: int, 1: int} [year, month] — defaults to the current
     * month when $ym is missing or doesn't match "YYYY-MM".
     */
    private function parseYearMonth(?string $ym): array
    {
        if ($ym && preg_match('/^(\d{4})-(\d{2})$/', $ym, $matches)) {
            return [(int) $matches[1], max(1, min(12, (int) $matches[2]))];
        }

        return [(int) now()->year, (int) now()->month];
    }
}
