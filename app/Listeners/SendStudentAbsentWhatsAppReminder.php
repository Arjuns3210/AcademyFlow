<?php

namespace App\Listeners;

use App\Events\StudentAbsentEvent;
use App\Services\ModuleAccessService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendStudentAbsentWhatsAppReminder implements ShouldQueue
{
    public function __construct(
        private ModuleAccessService $moduleAccess,
        private WhatsAppService $whatsApp,
    ) {}

    public function handle(StudentAbsentEvent $event): void
    {
        if (! $this->moduleAccess->hasAccess($event->institute, 'whatsapp_reminder')) {
            Log::info("WhatsApp attendance reminder skipped: institute #{$event->institute->id} plan does not include whatsapp_reminder.");

            return;
        }

        $student = $event->student;

        if (! $student->parent_mobile) {
            Log::info("WhatsApp attendance reminder skipped: student #{$student->id} has no parent_mobile on file.");

            return;
        }

        $this->whatsApp->sendTemplate(
            $event->institute,
            $student->parent_mobile,
            'attendance_absent',
            [
                'recipient_name' => $student->parent_name ?: $student->name,
                'student_name' => $student->name,
                'date' => Carbon::parse($event->attendanceDate)->format('d M Y'),
                'institute_name' => $event->institute->name,
            ],
            $student->id,
        );
    }
}
