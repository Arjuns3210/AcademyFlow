<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstituteModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'institute_id',
        'module_id',
        'custom_label',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * The institute's display name for this module, falling back to the
     * module's global default when no custom label has been set.
     */
    public function label(): string
    {
        return $this->custom_label ?: $this->module->name;
    }
}
