<?php

namespace App\Support;

/**
 * The two Meta-approved utility templates this app can send, and the named
 * parameters each one expects, in the order Meta's template body actually
 * fills them in. Not DB-editable yet — approving a new template with Meta
 * is a manual, out-of-band process anyway, so a hardcoded catalog here
 * matches reality until a later refinement makes this admin-manageable.
 */
class WhatsAppTemplates
{
    private const TEMPLATES = [
        'fee_reminder' => ['recipient_name', 'amount', 'month', 'institute_name', 'due_date'],
        'attendance_absent' => ['recipient_name', 'student_name', 'date', 'institute_name'],
    ];

    public static function exists(string $templateName): bool
    {
        return isset(self::TEMPLATES[$templateName]);
    }

    public static function paramKeys(string $templateName): array
    {
        return self::TEMPLATES[$templateName] ?? [];
    }

    /**
     * Maps a named params array into Meta's positional {{1}}, {{2}}...
     * body parameter order. Missing keys become an empty string rather than
     * throwing, since a malformed reminder is still worth logging as a
     * failed send (Meta will reject it) rather than crashing the caller.
     */
    public static function orderParams(string $templateName, array $params): array
    {
        return array_map(
            fn (string $key) => (string) ($params[$key] ?? ''),
            self::paramKeys($templateName)
        );
    }
}
