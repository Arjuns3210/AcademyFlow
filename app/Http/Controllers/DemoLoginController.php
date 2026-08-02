<?php

namespace App\Http\Controllers;

use App\Mail\DemoLoginNotification;
use App\Models\DemoLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DemoLoginController extends Controller
{
    /**
     * Hard cap, not a rolling window - once an IP has this many demo_leads
     * rows ever, it's blocked for good. Deliberately stricter than the
     * route's throttle:6,1 (that's just anti-flood; this is the real usage
     * cap, since every attempt already emails a real person).
     */
    private const MAX_ATTEMPTS_PER_IP = 2;

    /**
     * Captures the visitor's contact details, then logs them straight into
     * the shared seeded demo institute (Bright Coaching Classes) so sales
     * has a real lead to follow up on before anyone sees the live demo.
     * Every visitor shares the same demo account/data by deliberate choice.
     */
    public function store(Request $request): RedirectResponse
    {
        if (DemoLead::where('ip_address', $request->ip())->count() >= self::MAX_ATTEMPTS_PER_IP) {
            return redirect('/')->withErrors([
                'name' => "You've already tried the live demo the maximum number of times from this network. Please contact us directly for a walkthrough.",
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'institute_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $lead = DemoLead::create($data + ['ip_address' => $request->ip()]);

        $notifyEmails = array_filter(explode(',', (string) config('services.demo_login.notify_emails')));

        if ($notifyEmails !== []) {
            // Sent synchronously (not queued) by deliberate choice, since
            // nothing runs a persistent queue worker on this server yet. A
            // slow/unreachable SMTP server must not block the visitor from
            // actually entering the demo, so a send failure is only logged.
            try {
                Mail::to($notifyEmails)->send(new DemoLoginNotification($lead));
            } catch (Throwable $e) {
                Log::warning("Demo login notification email failed to send: {$e->getMessage()}");
            }
        }

        $demoUser = User::where('email', 'admin@brightcoaching.com')->firstOrFail();

        Auth::login($demoUser);
        $request->session()->regenerate();
        $request->session()->put('demo_login_at', now());

        $hours = \App\Http\Middleware\ExpireDemoSession::MAX_DEMO_SESSION_HOURS;

        return redirect()->route('dashboard')->with('status', "You're viewing a shared live demo (visible to other visitors, auto-logout after {$hours} hours). Ready for your own? Every plan includes a 7-day free trial.");
    }
}
