<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppLog extends Model
{
    // Same override as WhatsAppSetting - Eloquent's convention would derive
    // whats_app_logs (a capital boundary before "App").
    protected $table = 'whatsapp_logs';

    protected $fillable = [
        'institute_id',
        'source',
        'student_id',
        'template_name',
        'recipient_number',
        'status',
        'meta_message_id',
        'cost',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
        ];
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isSent(): bool
    {
        return in_array($this->status, ['sent', 'delivered', 'read'], true);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
