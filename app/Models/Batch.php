<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasStatus;
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
}
