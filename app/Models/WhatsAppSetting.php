<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppSetting extends Model
{
    // Eloquent's naming convention would derive whats_app_settings (a capital
    // boundary before "App") - overridden to match the schema as specified.
    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'institute_id',
        'phone_number_id',
        'access_token',
        'waba_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }
}
