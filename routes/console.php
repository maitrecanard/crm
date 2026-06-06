<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Relances automatiques : tous les jours à 8h (si `schedule:run` est branché en cron).
Schedule::command('crm:relances')->dailyAt('08:00');
