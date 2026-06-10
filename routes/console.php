<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Actualizar moratorios cada día a las 8 AM (hora Mérida)
Schedule::command('crea:update-moratorio')->dailyAt('08:00')->timezone('America/Merida');

// Enviar recordatorios de cuotas próximas a vencer (3 días antes)
Schedule::command('crea:recordatorios-pago')->dailyAt('09:00')->timezone('America/Merida');
