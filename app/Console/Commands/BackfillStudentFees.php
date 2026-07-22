<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Scopes\TenantScope;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Console\Command;

/**
 * One-time backfill for students admitted before Sprint 4, who were never
 * passed through StudentObserver::created() and so have no student_fees
 * row yet. Idempotent — safe to re-run; already-covered students are
 * skipped via whereDoesntHave('fee').
 */
class BackfillStudentFees extends Command
{
    protected $signature = 'students:backfill-fees';

    protected $description = 'Create a student_fees row (total_fee from their course, if any) for every existing student who does not have one yet';

    public function handle(): int
    {
        $created = 0;

        Student::withoutGlobalScope(TenantScope::class)
            ->whereDoesntHave('fee')
            ->chunkById(100, function ($students) use (&$created) {
                foreach ($students as $student) {
                    $totalFee = $student->course_id
                        ? (float) (Course::withoutGlobalScope(TenantScope::class)->find($student->course_id)?->fee ?? 0)
                        : 0;

                    StudentFee::create([
                        'tenant_id' => $student->tenant_id,
                        'student_id' => $student->id,
                        'total_fee' => $totalFee,
                    ]);

                    $created++;
                }
            });

        $this->info("Created {$created} student_fees row(s).");

        return self::SUCCESS;
    }
}
