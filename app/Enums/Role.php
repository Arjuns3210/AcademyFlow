<?php

namespace App\Enums;

/**
 * Coarse platform-level distinction only. Everything below Super Admin —
 * Institute Admin, Teacher, Accountant, or any custom role an Institute Admin
 * invents — is a 'tenant_user'; which one is determined entirely by
 * users.role_id (the assigned Role record and its role_permissions), not by
 * this enum. The enum exists solely to answer "is this a super admin" without
 * a join, which the User model's recursion-avoidance design depends on.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case TenantUser = 'tenant_user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::TenantUser => 'Tenant User',
        };
    }
}
