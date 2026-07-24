<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use App\Services\ModuleAccessService;
use App\Services\PermissionService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Deliberately does NOT use BelongsToTenant. Resolving the logged-in user
     * from the session queries this exact model — if a global scope here
     * called Auth::user() to decide how to filter, it would recurse into
     * itself indefinitely (Auth::user() -> query users -> scope calls
     * Auth::user() -> ...), hanging every authenticated request until PHP's
     * max_execution_time kills it. Tenant-filtering a user list (e.g.
     * "Institute Admin sees only their own institute's staff") must be done
     * with an explicit ->where('tenant_id', ...) in the controller instead.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (! $model->tenant_id && Auth::check() && Auth::user()->role !== Role::SuperAdmin) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class, 'tenant_id');
    }

    /**
     * The Teacher directory entry this login is linked to, if any.
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * The custom/system Role record carrying this user's module permissions.
     * Null for Super Admin, whose access is granted via the role enum instead.
     */
    public function roleRecord(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_id');
    }

    /**
     * Named canModule() (not can()) because Laravel's own Authorizable trait
     * — inherited via Authenticatable — already defines can()/canAny()/
     * cant()/cannot() for Gate-based policy checks. This is a distinct,
     * module-permission-matrix check, delegated to PermissionService.
     */
    public function canModule(string $moduleSlug, string $action): bool
    {
        return app(PermissionService::class)->can($this, $moduleSlug, $action);
    }

    /**
     * Distinct from canModule() - this checks whether the institute's
     * subscription plan includes a premium Phase 2/3 module (see
     * PlanModuleKeys), not whether this user's role has permission to use
     * an existing tenant module. Super Admin has no institute, so always false.
     */
    public function canAccessModule(string $moduleKey): bool
    {
        return $this->institute !== null
            && app(ModuleAccessService::class)->hasAccess($this->institute, $moduleKey);
    }
}
