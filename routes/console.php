<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Respaldos (spatie/laravel-backup). Se evitan las 02:00–03:00 por el cambio de horario.
 */
Schedule::command('backup:clean')->dailyAt('01:00')->withoutOverlapping()->onOneServer();
Schedule::command('backup:run')->dailyAt('01:30')->withoutOverlapping()->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:30')->onOneServer();
