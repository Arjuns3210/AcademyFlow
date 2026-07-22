<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('institute.reports.index', [
            'moduleLabel' => Auth::user()->institute->moduleLabel('reports'),
        ]);
    }

    public function students(Request $request): View
    {
        $students = $this->filteredStudentsQuery($request)
            ->with(['course', 'batch', 'fee'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $students->setCollection($this->buildStudentReportRows($students->getCollection()));

        return view('institute.reports.students', [
            'students' => $students,
            'filters' => $this->studentFilters($request),
            'courses' => Course::orderBy('name')->get(),
            'batches' => Batch::orderBy('name')->get(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('reports'),
        ]);
    }

    /**
     * Same filters as students(), but unpaginated — the report is browsed a
     * page at a time, but exporting it must cover every matching row.
     */
    public function exportStudents(Request $request): StreamedResponse
    {
        [$headers, $rows] = $this->studentReportTable($request);

        return $this->downloadCsv($headers, $rows, 'student-report.csv');
    }

    public function exportStudentsPdf(Request $request): Response
    {
        [$headers, $rows] = $this->studentReportTable($request);

        return $this->downloadTabularPdf('Student Report', null, $headers, $rows, null, 'student-report.pdf');
    }

    /**
     * @return array{0: array, 1: array} [headers, rows] — shared by both
     * exportStudents() (CSV) and exportStudentsPdf().
     */
    private function studentReportTable(Request $request): array
    {
        $students = $this->filteredStudentsQuery($request)
            ->with(['course', 'batch', 'fee'])
            ->orderBy('name')
            ->get();

        $decoratedRows = $this->buildStudentReportRows($students);

        $headers = ['Student Code', 'Name', 'Course', 'Batch', 'Admission Date', 'Status', 'Pending Fee', 'Attendance % (This Month)'];

        $rows = $decoratedRows->map(function (array $row) {
            $student = $row['student'];

            return [
                $student->student_code,
                $student->name,
                $student->course?->name ?? '—',
                $student->batch?->name ?? '—',
                $student->admission_date->format('d M Y'),
                ucfirst($student->status),
                $row['pendingFee'] !== null ? '₹'.number_format($row['pendingFee'], 2) : '—',
                $row['attendancePercentage'] !== null ? $row['attendancePercentage'].'%' : '—',
            ];
        })->all();

        return [$headers, $rows];
    }

    public function attendance(Request $request): View
    {
        $filters = $this->attendanceReportFilters($request);
        $mode = $request->query('mode') === 'detailed' ? 'detailed' : 'summary';

        $students = Student::activeFilteredBy($filters['student_id'], $filters['batch_id']);
        $daysInRange = $this->daysInRange($filters['from'], $filters['to']);
        $rangeTooLarge = $daysInRange > 31;

        $summaryRows = null;
        $detailRows = null;

        if ($mode === 'summary') {
            $summaryRows = $this->attendanceSummaryRows($students, $filters['from'], $filters['to']);
        } elseif (! $rangeTooLarge) {
            $detailRows = $this->attendanceDetailRows($students, $filters['from'], $filters['to']);
        }

        return view('institute.reports.attendance', [
            'mode' => $mode,
            'summaryRows' => $summaryRows,
            'detailRows' => $detailRows,
            'rangeTooLarge' => $rangeTooLarge,
            'daysInRange' => $daysInRange,
            'filters' => $filters,
            'allStudents' => Student::where('status', 'active')->orderBy('name')->get(),
            'batches' => Batch::orderBy('name')->get(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('reports'),
        ]);
    }

    public function exportAttendance(Request $request): StreamedResponse|RedirectResponse
    {
        $filters = $this->attendanceReportFilters($request);
        $mode = $request->query('mode') === 'detailed' ? 'detailed' : 'summary';

        if ($blocked = $this->blockOversizedAttendanceExport($filters, $mode, $request)) {
            return $blocked;
        }

        [$headers, $rows] = $this->attendanceReportTable($filters, $mode);

        return $this->downloadCsv($headers, $rows, "attendance-report-{$mode}.csv");
    }

    public function exportAttendancePdf(Request $request): Response|RedirectResponse
    {
        $filters = $this->attendanceReportFilters($request);
        $mode = $request->query('mode') === 'detailed' ? 'detailed' : 'summary';

        if ($blocked = $this->blockOversizedAttendanceExport($filters, $mode, $request)) {
            return $blocked;
        }

        [$headers, $rows] = $this->attendanceReportTable($filters, $mode);
        $subtitle = ucfirst($mode).' — '.Carbon::parse($filters['from'])->format('d M Y').' to '.Carbon::parse($filters['to'])->format('d M Y');

        return $this->downloadTabularPdf('Attendance Report', $subtitle, $headers, $rows, null, "attendance-report-{$mode}.pdf");
    }

    /**
     * The 31-day cap on Detailed mode applies to its export exactly as it
     * does on screen — generating a year's worth of per-day rows would be
     * just as unusable in a CSV/PDF as it would in the table.
     */
    private function blockOversizedAttendanceExport(array $filters, string $mode, Request $request): ?RedirectResponse
    {
        if ($mode !== 'detailed') {
            return null;
        }

        $daysInRange = $this->daysInRange($filters['from'], $filters['to']);

        if ($daysInRange <= 31) {
            return null;
        }

        return redirect()->route('institute.reports.attendance', $request->query())
            ->withErrors(['range' => "This range spans {$daysInRange} days — Detailed export is limited to 31 days at a time. Please narrow your range, or export Summary instead."]);
    }

    /**
     * @return array{0: array, 1: array} [headers, rows]
     */
    private function attendanceReportTable(array $filters, string $mode): array
    {
        $students = Student::activeFilteredBy($filters['student_id'], $filters['batch_id']);

        if ($mode === 'detailed') {
            $rows = $this->attendanceDetailRows($students, $filters['from'], $filters['to']);

            return [
                ['Student Code', 'Student Name', 'Date', 'Status'],
                $rows->map(fn (array $row) => [
                    $row['student']->student_code,
                    $row['student']->name,
                    Carbon::parse($row['date'])->format('d M Y'),
                    $row['status'] ? ucfirst($row['status']) : 'Not marked',
                ])->all(),
            ];
        }

        $rows = $this->attendanceSummaryRows($students, $filters['from'], $filters['to']);

        return [
            ['Student Code', 'Student Name', 'Present', 'Absent', 'Late', 'Leave', '% Attendance'],
            $rows->map(fn (array $row) => [
                $row['student']->student_code,
                $row['student']->name,
                $row['summary']['present'],
                $row['summary']['absent'],
                $row['summary']['late'],
                $row['summary']['leave'],
                $row['summary']['percentage'] !== null ? $row['summary']['percentage'].'%' : '—',
            ])->all(),
        ];
    }

    private function attendanceReportFilters(Request $request): array
    {
        return [
            'from' => (string) $request->query('from', now()->startOfMonth()->toDateString()),
            'to' => (string) $request->query('to', now()->endOfMonth()->toDateString()),
            'batch_id' => (string) $request->query('batch_id', ''),
            'student_id' => (string) $request->query('student_id', ''),
        ];
    }

    /**
     * One row per student — present/absent/late/leave counts + % across
     * the whole range. Reuses Attendance::summarize() (Sprint 5) rather
     * than re-deriving the formula for this report.
     */
    private function attendanceSummaryRows(EloquentCollection $students, string $from, string $to): Collection
    {
        $recordsByStudent = $this->attendanceRecordsByStudent($students, $from, $to);

        return $students->map(fn (Student $student) => [
            'student' => $student,
            'summary' => Attendance::summarize($recordsByStudent->get($student->id) ?? collect()),
        ]);
    }

    /**
     * One row per student per day in the range — a flat list (not a grid
     * like the Attendance module's own monthly view), meant for closer
     * analysis/export rather than at-a-glance scanning. Callers must check
     * the range isn't over 31 days first; this doesn't guard itself.
     */
    private function attendanceDetailRows(EloquentCollection $students, string $from, string $to): Collection
    {
        $recordsByStudent = $this->attendanceRecordsByStudent($students, $from, $to);
        $rows = collect();

        foreach ($students as $student) {
            $studentRecords = ($recordsByStudent->get($student->id) ?? collect())
                ->keyBy(fn ($record) => $record->attendance_date->toDateString());

            $cursor = Carbon::parse($from);
            $end = Carbon::parse($to);

            while ($cursor->lte($end)) {
                $rows->push([
                    'student' => $student,
                    'date' => $cursor->toDateString(),
                    'status' => $studentRecords->get($cursor->toDateString())?->status,
                ]);
                $cursor = $cursor->copy()->addDay();
            }
        }

        return $rows;
    }

    private function attendanceRecordsByStudent(EloquentCollection $students, string $from, string $to): Collection
    {
        return Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('attendance_date', [$from, $to])
            ->get()
            ->groupBy('student_id');
    }

    /**
     * absolute: true — diffInDays() is signed by argument order in this
     * Carbon version (confirmed while building Step 6B), and this must
     * never go negative regardless of which of from/to is chronologically
     * first — including a malformed reversed range — or the 31-day cap on
     * Detailed mode (and its export) silently stops applying.
     */
    private function daysInRange(string $from, string $to): int
    {
        return Carbon::parse($from)->diffInDays(Carbon::parse($to), absolute: true) + 1;
    }

    /**
     * Essentially the Fee Overview list (Sprint 4B) reframed as a report —
     * same "fetch, then filter the computed status accessor in PHP" shape,
     * with course/batch/admission-date-range filters added on top. Filters
     * by admission_date, not "when the fee row was created": StudentFee
     * rows are created in the same instant as the student (Sprint 4A's
     * StudentObserver), so those two dates are identical for any student
     * admitted after Sprint 4 shipped. The one case where they'd differ is
     * a pre-Sprint-4 student backfilled by students:backfill-fees — there,
     * "fee created" is just whenever that one-time command happened to run,
     * not a date staff actually care about. admission_date is the
     * meaningful business date in both cases.
     */
    public function fees(Request $request): View
    {
        $filters = $this->feeReportFilters($request);

        return view('institute.reports.fees', [
            'fees' => $this->paginateCollection($this->filteredFees($filters), $request),
            'filters' => $filters,
            'courses' => Course::orderBy('name')->get(),
            'batches' => Batch::orderBy('name')->get(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('reports'),
        ]);
    }

    public function exportFees(Request $request): StreamedResponse
    {
        [$headers, $rows] = $this->feeReportTable($request);

        return $this->downloadCsv($headers, $rows, 'fee-report.csv');
    }

    public function exportFeesPdf(Request $request): Response
    {
        [$headers, $rows] = $this->feeReportTable($request);

        return $this->downloadTabularPdf('Fee Report', null, $headers, $rows, null, 'fee-report.pdf');
    }

    /**
     * @return array{0: array, 1: array} [headers, rows]
     */
    private function feeReportTable(Request $request): array
    {
        $fees = $this->filteredFees($this->feeReportFilters($request));

        $headers = ['Student Code', 'Name', 'Course', 'Batch', 'Total Fee', 'Discount', 'Net Fee', 'Paid', 'Pending', 'Status'];

        $rows = $fees->map(fn (StudentFee $fee) => [
            $fee->student->student_code,
            $fee->student->name,
            $fee->student->course?->name ?? '—',
            $fee->student->batch?->name ?? '—',
            '₹'.number_format($fee->total_fee, 2),
            '₹'.number_format($fee->discount_amount, 2),
            '₹'.number_format($fee->net_fee, 2),
            '₹'.number_format($fee->paid_amount, 2),
            '₹'.number_format($fee->pending_amount, 2),
            ucfirst($fee->status),
        ])->all();

        return [$headers, $rows];
    }

    /**
     * The filtered-but-unpaginated set — fees() paginates this for the
     * on-screen view, feeReportTable() exports every row of it, matching
     * the same "export must cover all matching rows" requirement as the
     * Student Report.
     */
    private function filteredFees(array $filters): Collection
    {
        // withTrashed() on student specifically for display here — a
        // soft-deleted student's fee history must still show their name
        // (same "financial history survives" principle already applied to
        // FeeTransaction's collected_by/voided_by), not go blank. Found
        // during Sprint 8C's soft-delete audit: without this, a trashed
        // student's row silently rendered with no name at all.
        $query = StudentFee::with([
            'student' => fn ($q) => $q->withTrashed()->with('course', 'batch'),
            'transactions',
        ]);

        if ($filters['course_id'] !== '') {
            $query->whereHas('student', fn ($q) => $q->where('course_id', $filters['course_id']));
        }

        if ($filters['batch_id'] !== '') {
            $query->whereHas('student', fn ($q) => $q->where('batch_id', $filters['batch_id']));
        }

        if ($filters['admission_from'] !== '') {
            $query->whereHas('student', fn ($q) => $q->whereDate('admission_date', '>=', $filters['admission_from']));
        }

        if ($filters['admission_to'] !== '') {
            $query->whereHas('student', fn ($q) => $q->whereDate('admission_date', '<=', $filters['admission_to']));
        }

        $fees = $query->get()->sortBy(fn (StudentFee $fee) => $fee->student?->name)->values();

        if ($filters['status'] !== '') {
            $fees = $fees->filter(fn (StudentFee $fee) => $fee->status === $filters['status'])->values();
        }

        return $fees;
    }

    private function feeReportFilters(Request $request): array
    {
        return [
            'status' => (string) $request->query('status', ''),
            'course_id' => (string) $request->query('course_id', ''),
            'batch_id' => (string) $request->query('batch_id', ''),
            'admission_from' => (string) $request->query('admission_from', ''),
            'admission_to' => (string) $request->query('admission_to', ''),
        ];
    }

    /**
     * Unlike the Fee Report, every filter here (date range, payment_mode,
     * collected_by) is a real column — no in-memory filtering needed, so
     * this paginates natively rather than via paginateCollection().
     */
    public function collections(Request $request): View
    {
        $filters = $this->collectionReportFilters($request);

        $transactions = $this->filteredTransactionsQuery($filters)
            // withTrashed() for the same reason as the Fee Report — a
            // voided-or-not transaction against a since-deleted student must
            // still show their name in this financial history.
            ->with(['studentFee.student' => fn ($q) => $q->withTrashed(), 'collectedBy'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Total across every matching row, not just the current page —
        // a fresh query (not the paginated one above) so LIMIT/OFFSET from
        // pagination doesn't affect the sum.
        $totalCollected = $this->filteredTransactionsQuery($filters)
            ->whereNull('voided_at')
            ->sum('amount');

        return view('institute.reports.collections', [
            'transactions' => $transactions,
            'totalCollected' => $totalCollected,
            'filters' => $filters,
            'staffMembers' => User::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get(),
            'moduleLabel' => Auth::user()->institute->moduleLabel('reports'),
        ]);
    }

    private function filteredTransactionsQuery(array $filters): Builder
    {
        $query = FeeTransaction::whereBetween('payment_date', [$filters['from'], $filters['to']]);

        if ($filters['payment_mode'] !== '') {
            $query->where('payment_mode', $filters['payment_mode']);
        }

        if ($filters['collected_by'] !== '') {
            $query->where('collected_by', $filters['collected_by']);
        }

        return $query;
    }

    public function exportCollections(Request $request): StreamedResponse
    {
        [$headers, $rows, $summary] = $this->collectionReportTable($request);

        // Blank separator row, then the total as its own single-cell note —
        // simpler and more honest than force-fitting it into one of the
        // data columns, and CSV readers handle a ragged final row fine.
        return $this->downloadCsv($headers, [...$rows, [], [$summary]], 'collection-report.csv');
    }

    public function exportCollectionsPdf(Request $request): Response
    {
        [$headers, $rows, $summary] = $this->collectionReportTable($request);

        return $this->downloadTabularPdf('Collection Report', null, $headers, $rows, $summary, 'collection-report.pdf');
    }

    /**
     * @return array{0: array, 1: array, 2: string} [headers, rows, summary]
     */
    private function collectionReportTable(Request $request): array
    {
        $filters = $this->collectionReportFilters($request);

        $transactions = $this->filteredTransactionsQuery($filters)
            // withTrashed() for the same reason as the Fee Report — a
            // voided-or-not transaction against a since-deleted student must
            // still show their name in this financial history.
            ->with(['studentFee.student' => fn ($q) => $q->withTrashed(), 'collectedBy'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $totalCollected = $transactions->whereNull('voided_at')->sum('amount');

        $headers = ['Date', 'Student', 'Receipt No.', 'Amount', 'Mode', 'Collected By', 'Status'];

        $rows = $transactions->map(fn (FeeTransaction $transaction) => [
            $transaction->payment_date->format('d M Y'),
            $transaction->studentFee->student->name,
            $transaction->receipt_number,
            '₹'.number_format($transaction->amount, 2),
            $transaction->paymentModeLabel(),
            $transaction->collectedBy?->name ?? '—',
            $transaction->isVoided() ? 'VOIDED' : 'Active',
        ])->all();

        $summary = 'Total Collected (excl. voided): ₹'.number_format($totalCollected, 2);

        return [$headers, $rows, $summary];
    }

    private function collectionReportFilters(Request $request): array
    {
        return [
            'from' => (string) $request->query('from', now()->startOfMonth()->toDateString()),
            'to' => (string) $request->query('to', now()->endOfMonth()->toDateString()),
            'payment_mode' => (string) $request->query('payment_mode', ''),
            'collected_by' => (string) $request->query('collected_by', ''),
        ];
    }

    private function filteredStudentsQuery(Request $request): Builder
    {
        $query = Student::query();

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->query('batch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('admission_from')) {
            $query->whereDate('admission_date', '>=', $request->query('admission_from'));
        }

        if ($request->filled('admission_to')) {
            $query->whereDate('admission_date', '<=', $request->query('admission_to'));
        }

        return $query;
    }

    private function studentFilters(Request $request): array
    {
        return [
            'course_id' => (string) $request->query('course_id', ''),
            'batch_id' => (string) $request->query('batch_id', ''),
            'status' => (string) $request->query('status', ''),
            'admission_from' => (string) $request->query('admission_from', ''),
            'admission_to' => (string) $request->query('admission_to', ''),
        ];
    }

    /**
     * Decorates a set of students with this month's attendance % and
     * pending fee. Attendance is bulk-fetched in one query and reduced via
     * Attendance::summarize() (Sprint 5) rather than re-deriving the
     * present/late/percentage formula here — this matters most for
     * exportStudents(), which can cover far more rows than one page.
     * pending_fee stays a plain per-row accessor call (StudentFee::
     * pendingAmount()), matching the same cost already accepted on the Fee
     * Overview list (Sprint 4B) — that accessor isn't built to take a
     * pre-fetched collection the way Attendance::summarize() is.
     */
    private function buildStudentReportRows(EloquentCollection $students): Collection
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $attendanceByStudent = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereBetween('attendance_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy('student_id');

        return $students->map(function (Student $student) use ($attendanceByStudent) {
            $summary = Attendance::summarize($attendanceByStudent->get($student->id) ?? collect());

            return [
                'student' => $student,
                'attendancePercentage' => $summary['percentage'],
                'pendingFee' => $student->fee?->pending_amount,
            ];
        });
    }

    /**
     * @param  array<int, array>  $rows  each row already flattened to plain
     *                                   display-ready values, in column order
     */
    private function downloadCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Same generic tabular shape as downloadCsv(), rendered as a simple
     * clean table via the shared PDF template rather than anything fancy —
     * reuses barryvdh/laravel-dompdf exactly as set up in Sprint 4C.
     */
    private function downloadTabularPdf(string $title, ?string $subtitle, array $headers, array $rows, ?string $summary, string $filename): Response
    {
        $pdf = Pdf::loadView('institute.reports.pdf.tabular', [
            'title' => $title,
            'subtitle' => $subtitle,
            'headers' => $headers,
            'rows' => $rows,
            'summary' => $summary,
            'institute' => Auth::user()->institute,
        ]);

        return $pdf->download($filename);
    }
}
