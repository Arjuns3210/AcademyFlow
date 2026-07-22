<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the /super-admin/* area. Deliberately separate from the
 * `permission` middleware — Super Admin isn't governed by the
 * module/role permission matrix at all, just this coarse role check.
 */
class EnsureUserIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() && $request->user()->role === Role::SuperAdmin,
            403,
            'This area is restricted to Super Admin.'
        );

        return $next($request);
    }
}
