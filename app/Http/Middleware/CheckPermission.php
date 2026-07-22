<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('permission:students,view')
 *
 * Assumes an auth middleware already ran first (e.g. the 'auth' middleware
 * in the same route group) — this only checks the module/action permission
 * for whichever user is already authenticated, it doesn't itself enforce
 * that a user is logged in.
 */
class CheckPermission
{
    public function __construct(private PermissionService $permissions) {}

    public function handle(Request $request, Closure $next, string $moduleSlug, string $action): Response
    {
        $user = $request->user();

        abort_if(! $user, 403, 'You do not have permission to perform this action.');

        abort_unless(
            $this->permissions->can($user, $moduleSlug, $action),
            403,
            'You do not have permission to perform this action.'
        );

        return $next($request);
    }
}
