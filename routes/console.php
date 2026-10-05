<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('crm:dispatch-sends')->everyMinute()->withoutOverlapping();

Schedule::command('crm:send-task-digest')->weekdays()->dailyAt('08:00')->timezone(config('crm.timezone'));
