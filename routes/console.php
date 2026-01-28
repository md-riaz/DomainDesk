<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Domain Sync Scheduler
Schedule::command('domain:sync-status --days=30 --limit=200')
    ->dailyAt('02:00')
    ->name('sync-expiring-domains-status')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('domain:sync --limit=500')
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('weekly-full-domain-sync')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('tld:sync-prices')
    ->dailyAt('04:00')
    ->name('daily-tld-price-sync')
    ->withoutOverlapping()
    ->runInBackground();
