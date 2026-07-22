<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Student extends Model
{
    use BelongsToTenant, HasFactory, HasStatus, SoftDeletes;

    /**
     * Attempts before generateUniqueStudentCode() gives up guessing and
     * falls back to a random suffix — see performInsert() below.
     */
    private const MAX_CODE_ATTEMPTS = 5;

    protected $fillable = [
        'tenant_id',
        'student_code',
        'name',
        'photo',
        'dob',
        'gender',
        'parent_name',
        'parent_mobile',
        'parent_email',
        'address',
        'course_id',
        'batch_id',
        'status',
        'admission_date',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'admission_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $student) {
            if (! $student->admission_date) {
                $student->admission_date = now()->toDateString();
            }

            if (! $student->student_code) {
                $student->student_code = static::generateUniqueStudentCode($student);
            }
        });
    }

    public function isDropped(): bool
    {
        return $this->status === 'dropped';
    }

    /**
     * Active students, optionally narrowed to one specific student or one
     * batch — a specific student takes priority over a batch if both are
     * given. Shared by the Attendance module's daily/monthly views (Sprint
     * 5) and the Attendance Report (Sprint 6) so this filtering convention
     * only lives in one place.
     */
    public static function activeFilteredBy(string $studentId, string $batchId): Collection
    {
        $query = static::where('status', 'active');

        if ($studentId !== '') {
            $query->where('id', $studentId);
        } elseif ($batchId !== '') {
            $query->where('batch_id', $batchId);
        }

        return $query->orderBy('name')->get();
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function fee(): HasOne
    {
        return $this->hasOne(StudentFee::class);
    }

    /**
     * Wraps the insert in a retry loop that regenerates student_code on a
     * genuine unique-constraint collision. The creating() hook above makes
     * a good-faith first guess, but two concurrent admissions can both pass
     * that guess before either commits — the database's unique index on
     * (tenant_id, student_code) is the actual race-condition guard, and this
     * catches its violation to retry with a fresh code rather than fail the
     * whole admission.
     */
    protected function performInsert(Builder $query)
    {
        for ($attempt = 1; $attempt <= self::MAX_CODE_ATTEMPTS; $attempt++) {
            try {
                return parent::performInsert($query);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt === self::MAX_CODE_ATTEMPTS || ! str_contains($e->getMessage(), 'student_code')) {
                    throw $e;
                }

                $this->student_code = static::generateUniqueStudentCode($this);
            }
        }

        return false;
    }

    private static function generateUniqueStudentCode(self $student): string
    {
        $institute = $student->tenant_id ? Institute::find($student->tenant_id) : null;
        $shortCode = $institute?->shortCode() ?? 'ST';
        $year = Carbon::parse($student->admission_date ?? now())->year;

        $baseCount = static::withoutGlobalScopes()
            ->withTrashed()
            ->where('tenant_id', $student->tenant_id)
            ->where('student_code', 'like', "{$shortCode}-{$year}-%")
            ->count();

        for ($offset = 1; $offset <= self::MAX_CODE_ATTEMPTS; $offset++) {
            $candidate = sprintf('%s-%d-%04d', $shortCode, $year, $baseCount + $offset);

            $exists = static::withoutGlobalScopes()
                ->withTrashed()
                ->where('tenant_id', $student->tenant_id)
                ->where('student_code', $candidate)
                ->exists();

            if (! $exists) {
                return $candidate;
            }
        }

        // Vanishingly unlikely: every guessed sequence number was taken.
        // A random suffix guarantees termination instead of looping forever.
        return sprintf('%s-%d-%s', $shortCode, $year, Str::upper(Str::random(4)));
    }
}
