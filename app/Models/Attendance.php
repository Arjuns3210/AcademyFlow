<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Attendance extends Model
{
    use BelongsToTenant;

    // Eloquent would otherwise guess "attendances" — the migration and the
    // rest of this module deliberately use the singular, uncountable form.
    protected $table = 'attendance';

    protected $fillable = [
        'tenant_id',
        'student_id',
        'batch_id',
        'attendance_date',
        'status',
        'marked_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    /**
     * Upserts by (tenant_id, student_id, attendance_date) — marking the
     * same student on the same date again updates that one row instead of
     * creating a duplicate, per the unique constraint in the migration.
     */
    public static function mark(
        int $tenantId,
        int $studentId,
        ?int $batchId,
        string $attendanceDate,
        string $status,
        int $markedBy,
        ?string $remarks = null,
    ): self {
        return static::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'student_id' => $studentId,
                'attendance_date' => $attendanceDate,
            ],
            [
                'batch_id' => $batchId,
                'status' => $status,
                'marked_by' => $markedBy,
                'remarks' => $remarks,
            ]
        );
    }

    /**
     * present/absent/late/leave counts + percentage from an already-fetched
     * collection of records — pure calculation, no query, so callers that
     * already have records in hand (e.g. AttendanceController::monthlyRowsFor()
     * batching many students in one query) don't pay for a second one.
     *
     * @return array{present: int, absent: int, late: int, leave: int, percentage: float|null}
     */
    public static function summarize(Collection $records): array
    {
        $summary = ['present' => 0, 'absent' => 0, 'late' => 0, 'leave' => 0];

        foreach ($records as $record) {
            $summary[$record->status]++;
        }

        $totalMarked = array_sum($summary);

        // Present + late both count as "attended"; leave counts as a
        // tracked day but not an attended one. Null (not 0%) when nothing
        // was marked at all, so an unmarked month doesn't misleadingly
        // read as 0% attendance.
        $summary['percentage'] = $totalMarked > 0
            ? round(($summary['present'] + $summary['late']) / $totalMarked * 100, 1)
            : null;

        return $summary;
    }

    /**
     * summarize(), scoped to one student's one month — the single query
     * this needs is cheap on its own (unlike looping it per-student for a
     * whole batch, which is what monthlyRowsFor() avoids by batching).
     */
    public static function monthlySummaryFor(int $studentId, ?int $year = null, ?int $month = null): array
    {
        $startOfMonth = Carbon::create($year ?? now()->year, $month ?? now()->month, 1)->startOfMonth();

        $records = static::where('student_id', $studentId)
            ->whereBetween('attendance_date', [$startOfMonth->toDateString(), $startOfMonth->copy()->endOfMonth()->toDateString()])
            ->get();

        return static::summarize($records);
    }
}
