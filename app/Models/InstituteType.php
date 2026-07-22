<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstituteType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Default module template for this type. Read only at institute-creation
     * time to pre-select modules — never consulted again afterward.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'institute_type_modules');
    }

    public function institutes(): HasMany
    {
        return $this->hasMany(Institute::class);
    }
}
