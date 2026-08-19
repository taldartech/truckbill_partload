<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscription:apply-upcoming')
    ->dailyAt('00:15')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onOneServer()
    ->description('Apply pending upcoming subscriptions after term end');

Schedule::command('app:dbbackup')->everyThreeHours()->withoutOverlapping();