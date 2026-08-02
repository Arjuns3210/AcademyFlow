<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caps a shared demo-account session at 2 hours (DemoLoginController stamps
 * session('demo_login_at') at login). Real institute accounts never set that
 * key, so this is a no-op for them regardless of how old their session is.
 */
class ExpireDemoSession
{
    public const MAX_DEMO_SESSION_HOURS = 2;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_demo_account) {
            return $next($request);
        }

        $loginAt = $request->session()->get('demo_login_at');

        if ($loginAt && now()->diffInHours($loginAt) >= self::MAX_DEMO_SESSION_HOURS) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')->with('status', 'Your 2-hour live demo session has ended. Feel free to request a new one anytime.');
        }

        return $next($request);
    }
}
