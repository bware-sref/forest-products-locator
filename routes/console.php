<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Daily statistics history (App\Statistics\Snapshots). Runs late in the day so it reflects that day's changes.
 * Needs the scheduler running on the server (`php artisan schedule:run` every minute); without it, the Statistics
 * pages still record a snapshot the first time someone opens them each day.
 */
Schedule::command('statistics:snapshot')->dailyAt('23:30');
