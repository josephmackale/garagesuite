<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:queue-reminders')
    ->dailyAt('09:00'); // Kenya-friendly time

Schedule::command('subscriptions:sync-status')
    ->dailyAt('00:15');
