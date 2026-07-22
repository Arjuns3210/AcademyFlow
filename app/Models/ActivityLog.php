<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience for recording an entry against any model, e.g.:
     * ActivityLog::record('discount_updated', $studentFee, 'Discount changed from ...');
     *
     * $tenantId is only needed when the acting user has no tenant of their
     * own — a Super Admin blocking an institute or activating a
     * subscription must log against the *affected* institute, not
     * Auth::user()->tenant_id (which is null for Super Admin), or the
     * entry would be invisible to the institute it's actually about.
     */
    public static function record(string $action, Model $subject, string $description, ?int $tenantId = null): self
    {
        return static::create([
            'tenant_id' => $tenantId ?? Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => $description,
        ]);
    }
}
