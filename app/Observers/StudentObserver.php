<?php

namespace App\Observers;

use App\Models\Course;
use App\Models\Student;
use App\Models\StudentFee;

/**
 * Keeps each student's StudentFee row in sync with their course assignment.
 * A student_fees row is created immediately on admission (total_fee=0 if no
 * course is assigned yet), and total_fee is re-synced to the current
 * course's fee whenever course_id changes. This can move total_fee (and
 * therefore net_fee) up or down even after payments already exist against
 * the old figure — see StudentFee::pendingAmount() for why that's treated
 * as a legitimate credit/owed state rather than an error.
 *
 * Lives here (not on Student itself) so the Sprint 2 Student model stays
 * unaware of the Fees module built two sprints later.
 */
class StudentObserver
{
    public function created(Student $student): void
    {
        StudentFee::create([
            'tenant_id' => $student->tenant_id,
            'student_id' => $student->id,
            'total_fee' => $this->courseFee($student),
        ]);
    }

    public function updated(Student $student): void
    {
        if (! $student->wasChanged('course_id')) {
            return;
        }

        StudentFee::updateOrCreate(
            ['student_id' => $student->id],
            ['tenant_id' => $student->tenant_id, 'total_fee' => $this->courseFee($student)]
        );
    }

    private function courseFee(Student $student): float
    {
        return $student->course_id ? (float) (Course::find($student->course_id)?->fee ?? 0) : 0;
    }
}
