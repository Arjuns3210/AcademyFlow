<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Flips trial subscriptions past trial_ends_at, and active subscriptions past current_period_end, to expired';

    /**
     * Idempotent by construction, not by any extra locking: each is a bulk
     * UPDATE ... WHERE status = X, so a row that already flipped to expired
     * simply stops matching the filter on the next run — running this twice
     * in a row (or concurrently) is a no-op the second time, not a double
     * transition.
     */
    public function handle(): int
    {
        $trialsExpired = Subscription::where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->update(['status' => 'expired']);

        $activeExpired = Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now()->toDateString())
            ->update(['status' => 'expired']);

        $this->info("Expired {$trialsExpired} trial subscription(s) and {$activeExpired} active subscription(s).");

        return self::SUCCESS;
    }
}
