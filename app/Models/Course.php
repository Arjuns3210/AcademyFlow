<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use BelongsToTenant, HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'duration',
        'fee',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
        ];
    }

    /**
     * Matched by students.course_id — a plain column until the Sprint 3D
     * migration adds the real foreign key, but the relation query works
     * identically either way.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'course_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Splits the stored "6 Months" string back into a number + unit pair
     * so the edit form can pre-fill its number input and unit <select>.
     * Falls back to blanks for legacy/free-text values (e.g. "@@@2") that
     * predate the structured duration fields.
     *
     * @return array{value: string, unit: string}
     */
    public function durationParts(): array
    {
        $blank = ['value' => '', 'unit' => ''];

        if (! $this->duration || ! preg_match('/^(\d+)\s*([A-Za-z]+)$/', trim($this->duration), $matches)) {
            return $blank;
        }

        $units = ['Weeks' => 'Weeks', 'Week' => 'Weeks', 'Months' => 'Months', 'Month' => 'Months', 'Years' => 'Years', 'Year' => 'Years'];
        $unit = $units[ucfirst(strtolower($matches[2]))] ?? null;

        if (! $unit) {
            return $blank;
        }

        return ['value' => $matches[1], 'unit' => $unit];
    }
}
