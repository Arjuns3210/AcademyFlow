<?php

namespace App\Events;

use App\Models\Institute;
use App\Models\StudentFee;

/**
 * Not fired automatically yet - no due_date concept exists on StudentFee
 * today (status is purely computed from paid vs net fee), so there's no
 * real "past due" moment to hook. Dispatched only from the manual "Send
 * Reminder" button for now (FeeController::sendReminder). A daily scheduled
 * scan can fire this automatically once a due_date column and a way to set
 * it actually exist - deliberately deferred rather than invented.
 */
class FeeDueEvent
{
    public function __construct(
        public readonly Institute $institute,
        public readonly StudentFee $studentFee,
        public readonly ?string $dueDateLabel = null,
    ) {}
}
