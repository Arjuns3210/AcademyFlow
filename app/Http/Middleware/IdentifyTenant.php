<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the authenticated user's institute and shares it with the request
 * lifecycle. Super Admins have no tenant and are left untouched so they can
 * operate across all institutes. Blocks access if the institute has been
 * deactivated.
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role === Role::SuperAdmin) {
            return $next($request);
        }

        $institute = $user->institute;

        abort_if(! $institute, 403, 'Your account is not linked to an institute.');
        abort_if($institute->status !== 'active', 403, 'Your institute account has been deactivated.');

        app()->instance('tenant', $institute);

        return $next($request);
    }
}
