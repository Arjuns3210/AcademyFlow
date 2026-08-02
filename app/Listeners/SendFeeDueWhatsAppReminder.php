<?php

namespace App\Listeners;

use App\Events\FeeDueEvent;
use App\Services\ModuleAccessService;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendFeeDueWhatsAppReminder implements ShouldQueue
{
    public function __construct(
        private ModuleAccessService $moduleAccess,
        private WhatsAppService $whatsApp,
    ) {}

    public function handle(FeeDueEvent $event): void
    {
        if (! $this->moduleAccess->hasAccess($event->institute, 'whatsapp_reminder')) {
            Log::info("WhatsApp fee reminder skipped: institute #{$event->institute->id} plan does not include whatsapp_reminder.");

            return;
        }

        $studentFee = $event->studentFee;
        $student = $studentFee->student;

        $recipientNumber = $student->parent_mobile ?: $student->student_mobile;

        if (! $recipientNumber) {
            Log::info("WhatsApp fee reminder skipped: student #{$student->id} has no parent_mobile or student_mobile on file.");
 
            return;
        }

        $this->whatsApp->sendTemplate(
            $event->institute,
            $recipientNumber,
            'fee_reminder',
            [
                'recipient_name' => $student->parent_name ?: $student->name,
                'amount' => number_format((float) $studentFee->pending_amount, 2),
                'month' => now()->format('F'),
                'institute_name' => $event->institute->name,
                'due_date' => $event->dueDateLabel ?? 'at your earliest convenience',
            ],
            $student->id,
        );
    }
}
