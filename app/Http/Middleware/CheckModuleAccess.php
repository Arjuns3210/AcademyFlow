<?php

namespace App\Http\Middleware;

use App\Services\ModuleAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('module:whatsapp_reminder')
 *
 * Unlike CheckPermission (which 403s — the user is allowed to be here, just
 * not to do this specific action), a plan-gated module is something the
 * institute hasn't bought at all, so this redirects to a dedicated "feature
 * locked" page explaining what plan unlocks it instead of a bare 403.
 */
class CheckModuleAccess
{
    public function __construct(private ModuleAccessService $moduleAccess) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();

        if (! $user?->institute || ! $this->moduleAccess->hasAccess($user->institute, $moduleKey)) {
            return redirect()->route('institute.feature-locked', ['moduleKey' => $moduleKey]);
        }

        return $next($request);
    }
}
