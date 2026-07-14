<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('scp:process-expired-trials')->dailyAt('01:00');
Schedule::command('scp:suspend-overdue-subscriptions')->dailyAt('01:30');
Schedule::command('scp:alert-dispute-deadlines')->dailyAt('09:00');
Schedule::command('scp:reconcile-nightly')->dailyAt('02:00');
Schedule::command('scp:reconcile-pending-payments')->everyFifteenMinutes();
Schedule::command('cms:process-scheduled-content')->everyMinute();
Schedule::command('catalog:process-scheduled-collections')->everyMinute();
Schedule::command('checkout:expire-gift-cards')->hourly();
Schedule::command('tenancy:verify-custom-domains')->everyFiveMinutes();
Schedule::command('messaging:poll-outbox')->everyMinute();
Schedule::command('ops:synthetic-checkout-probe')->everyFiveMinutes();
