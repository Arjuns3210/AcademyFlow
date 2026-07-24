<?php

namespace App\Events;

use App\Models\Institute;
use App\Models\Student;

class StudentAbsentEvent
{
    public function __construct(
        public readonly Institute $institute,
        public readonly Student $student,
        public readonly string $attendanceDate,
    ) {}
}
