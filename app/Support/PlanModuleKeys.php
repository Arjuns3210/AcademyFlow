<?php

namespace App\Support;

/**
 * The fixed catalog of Phase 2/3 premium module keys that a subscription
 * plan can unlock (App\Models\PlanModule.module_key). Deliberately separate
 * from App\Models\Module, which governs a different axis entirely — which
 * of the original Sprint 1-8 feature areas (students, fees, attendance...)
 * an institute type/institute has enabled. This list is plan-based gating
 * for newer premium add-ons, not the tenant module catalog.
 */
class PlanModuleKeys
{
    private const KEYS = [
        'whatsapp_reminder' => 'WhatsApp Reminders',
        'sms' => 'SMS Reminders',
        'qr_attendance' => 'QR Code Attendance',
        'student_login' => 'Student Login Portal',
        'parent_login' => 'Parent Login Portal',
        'dashboard_charts' => 'Dashboard Charts',
    ];

    public static function all(): array
    {
        return self::KEYS;
    }

    public static function keys(): array
    {
        return array_keys(self::KEYS);
    }

    public static function label(string $key): string
    {
        return self::KEYS[$key] ?? $key;
    }
}
