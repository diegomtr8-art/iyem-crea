<?php

use App\Jobs\CalcularCarteraActiva;
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

// Calcular el total de cartera activa para la tarjeta del panel (se lee de caché)
Schedule::job(new CalcularCarteraActiva)->dailyAt('07:00')->timezone('America/Merida');

// Respaldo diario de la base en horario de baja actividad (ver docs/RESPALDO.md)
Schedule::command('crea:respaldo-base')->dailyAt('03:00')->timezone('America/Merida');