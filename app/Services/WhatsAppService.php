<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\WhatsAppLog;
use App\Models\WhatsAppSetting;
use App\Support\WhatsAppTemplates;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class WhatsAppService
{
    /**
     * Sends one Meta-approved utility template message and logs the attempt
     * to whatsapp_logs regardless of outcome. Returns whether Meta actually
     * accepted it — callers that need to react to failure (9C's listeners)
     * check the return value; everyone else can just fire-and-forget since
     * the log is the durable record either way.
     */
    public function sendTemplate(
        Institute $institute,
        string $recipientNumber,
        string $templateName,
        array $params,
        ?int $studentId = null,
    ): bool {
        if (! WhatsAppTemplates::exists($templateName)) {
            throw new InvalidArgumentException("Unknown WhatsApp template: {$templateName}");
        }

        $settings = $this->resolveSettings($institute);

        if (! $settings) {
            return $this->logAndFail($institute, $studentId, $templateName, $recipientNumber,
                'No active WhatsApp number configured for this institute or the platform default.');
        }

        $orderedParams = WhatsAppTemplates::orderParams($templateName, $params);
        $apiVersion = config('services.whatsapp.api_version');

        try {
            $response = Http::timeout(10)
                ->withToken($settings->access_token)
                ->post("https://graph.facebook.com/{$apiVersion}/{$settings->phone_number_id}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $recipientNumber,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'en'],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => array_map(
                                fn (string $text) => ['type' => 'text', 'text' => $text],
                                $orderedParams
                            ),
                        ]],
                    ],
                ]);
        } catch (ConnectionException $e) {
            return $this->logAndFail($institute, $studentId, $templateName, $recipientNumber,
                "Connection error: {$e->getMessage()}");
        }

        $messageId = $response->json('messages.0.id');
        $success = $response->successful() && $messageId !== null;

        WhatsAppLog::create([
            'institute_id' => $institute->id,
            'student_id' => $studentId,
            'template_name' => $templateName,
            'recipient_number' => $recipientNumber,
            'status' => $success ? 'sent' : 'failed',
            'meta_message_id' => $messageId,
            'error_message' => $success ? null : ($response->json('error.message') ?? "Unexpected response (HTTP {$response->status()})"),
        ]);

        return $success;
    }

    /**
     * For the read-only "WhatsApp Settings" status display (9D) - reuses
     * the exact same resolution resolveSettings() uses for sending, so the
     * status shown can never drift out of sync with what actually happens
     * when a reminder is sent.
     */
    public function connectionStatus(Institute $institute): string
    {
        $candidate = $this->resolveSettings($institute);

        if (! $candidate) {
            return 'none';
        }

        return $candidate->institute_id === null ? 'demo' : 'own';
    }

    /**
     * An institute's own active settings win; otherwise the single
     * institute_id = null row (the platform demo number) is used, which is
     * how a trial/Starter-credentialless institute still gets to send
     * during a sales trial. Either way, both phone_number_id and
     * access_token must actually be present - an "active" row with nothing
     * filled in yet isn't usable.
     */
    private function resolveSettings(Institute $institute): ?WhatsAppSetting
    {
        $candidate = WhatsAppSetting::where('institute_id', $institute->id)->where('is_active', true)->first()
            ?? WhatsAppSetting::whereNull('institute_id')->where('is_active', true)->latest()->first();

        if (! $candidate || ! $candidate->phone_number_id || ! $candidate->access_token) {
            return null;
        }

        return $candidate;
    }

    private function logAndFail(Institute $institute, ?int $studentId, string $templateName, string $recipientNumber, string $reason): bool
    {
        WhatsAppLog::create([
            'institute_id' => $institute->id,
            'student_id' => $studentId,
            'template_name' => $templateName,
            'recipient_number' => $recipientNumber,
            'status' => 'failed',
            'error_message' => $reason,
        ]);

        return false;
    }
}
