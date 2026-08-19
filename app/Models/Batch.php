<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use BelongsToTenant, HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'course_id',
        'teacher_id',
        'time_slot',
        'capacity',
        'status',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Matched by students.batch_id — a plain column until the Sprint 3D
     * migration adds the real foreign key, but the relation query works
     * identically either way.
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'batch_id');
    }

    /**
     * Computed on read rather than cached — small-scale data per Sprint 3
     * scope, and always correct even as students are (re)assigned.
     *
     * $excludingStudentId excludes one student from the count — used when
     * checking capacity for a student who is already in this batch and is
     * simply being re-saved, so they don't count against their own slot.
     */
    public function enrolledCount(?int $excludingStudentId = null): int
    {
        return $this->students()
            ->where('status', 'active')
            ->when($excludingStudentId, fn ($query) => $query->where('id', '!=', $excludingStudentId))
            ->count();
    }

    public function isFull(?int $excludingStudentId = null): bool
    {
        return $this->capacity !== null && $this->enrolledCount($excludingStudentId) >= $this->capacity;
    }

    /**
     * Splits the stored "4:00 PM - 5:30 PM" string back into 24-hour
     * start/end values so the edit form can pre-fill its two <input
     * type="time"> fields. Falls back to blanks for legacy free-text
     * values (e.g. "5") that predate the time-picker fields.
     *
     * @return array{start: string, end: string}
     */
    public function timeSlotParts(): array
    {
        $blank = ['start' => '', 'end' => ''];

        if (! $this->time_slot || ! str_contains($this->time_slot, '-')) {
            return $blank;
        }

        [$start, $end] = array_map('trim', explode('-', $this->time_slot, 2));

        try {
            return [
                'start' => Carbon::parse($start)->format('H:i'),
                'end' => Carbon::parse($end)->format('H:i'),
            ];
        } catch (\Exception $e) {
            return $blank;
        }
    }
}
