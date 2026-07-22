<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:expire')->daily()->withoutOverlapping();

// Database-only backup (see config/backup.php) + its companion cleanup
// command, which enforces the retention policy in config('backup.cleanup').
// Off-peak time, cleanup runs after so it's pruning against the backup
// that just landed rather than yesterday's.
Schedule::command('backup:run --only-db')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:clean')->dailyAt('02:30')->withoutOverlapping();
