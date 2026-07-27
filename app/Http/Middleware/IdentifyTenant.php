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

        // loginBlockReason() only used to be checked at the login form
        // itself (LoginRequest) - a session created before a trial lapsed
        // (or a subscription expired/was cancelled) kept working
        // indefinitely for as long as that session lasted, since nothing
        // re-checked it on later requests. Centralizing on the same method
        // here means login and every subsequent request agree.
        $blockReason = $institute->subscription?->loginBlockReason();
        abort_if($blockReason, 403, $blockReason);

        app()->instance('tenant', $institute);

        return $next($request);
    }
}
