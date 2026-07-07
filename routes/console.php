<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Relances automatiques : tous les jours à 8h (si `schedule:run` est branché en cron).
Schedule::command('crm:relances')->dailyAt('08:00');

// Surveillance des factures mensuelles non envoyées : alerte quotidienne (8h15).
Schedule::command('crm:factures-alertes')->dailyAt('08:15');

// Rappel des tâches transmises par les partenaires : 7h en semaine, 10h le week-end.
Schedule::command('crm:rappels-partenaires')->weekdays()->at('07:00');
Schedule::command('crm:rappels-partenaires')->weekends()->at('10:00');

// Relance des tickets en cours : matin (8h) et soir (18h).
Schedule::command('crm:relances-tickets --moment=matin')->dailyAt('08:00');
Schedule::command('crm:relances-tickets --moment=soir')->dailyAt('18:00');
