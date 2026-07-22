<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use BelongsToTenant, HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'qualification',
        'mobile',
        'email',
        'subjects',
        'photo',
        'user_id',
        'status',
    ];

    /**
     * The optional Staff login this directory entry is linked to — most
     * teachers won't have one; see the migration for why.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }
}
