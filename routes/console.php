<?php

use App\Jobs\CalcularCarteraActiva;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Actualizar moratorios cada día a las 8 AM (hora Mérida)
Schedule::command('crea:update-moratorio')->dailyAt('08:00')->timezone('America/Merida');

// Encolar recordatorios de cuotas próximas a vencer (3 días antes); los envía procesar-cola
Schedule::command('crea:recordatorios-pago')->dailyAt('09:00')->timezone('America/Merida');

// Calcular el total de cartera activa para la tarjeta del panel (se lee de caché)
Schedule::job(new CalcularCarteraActiva)->dailyAt('07:00')->timezone('America/Merida');

// Respaldo diario de la base en horario de baja actividad (ver docs/RESPALDO.md)
Schedule::command('crea:respaldo-base')->dailyAt('03:00')->timezone('America/Merida');

// Enviar los correos encolados (recordatorios). Hostinger no permite procesos permanentes, así que
// el mismo cron del scheduler vacía la cola cada minuto, solo cuando hay correos pendientes.
// Si un correo agota sus intentos, esta tarea falla: queda en la bitácora y avisa a los administradores.
Schedule::call(function () {
    $fallidosAntes = DB::table('failed_jobs')->count();

    Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 50, '--sleep' => 0]);

    $nuevos = DB::table('failed_jobs')->count() - $fallidosAntes;

    if ($nuevos > 0) {
        throw new RuntimeException("{$nuevos} correo(s) no se pudieron enviar después de 3 intentos. El detalle está en storage/logs/laravel.log.");
    }
})->name('procesar-cola')->everyMinute()->withoutOverlapping(10)->when(fn () => DB::table('jobs')->exists());
