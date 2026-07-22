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
}
