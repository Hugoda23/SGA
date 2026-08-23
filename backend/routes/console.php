<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generar notificaciones automáticas diariamente a las 6:00 AM
Schedule::command('sga:generar-notificaciones')->dailyAt('06:00');
