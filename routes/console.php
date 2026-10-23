<?php

use App\Jobs\CalcularCarteraActiva;
use App\Models\BitacoraTareaProgramada;
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

// Auditoría de integridad (solo reporta, no corrige).
// Pendiente: que se decida si se activa y a qué hora.
// Schedule::command('crea:auditar')->dailyAt('07:00')->timezone('America/Merida');

// Calcular el total de cartera activa para la tarjeta del panel (se lee de caché)
Schedule::job(new CalcularCarteraActiva)->dailyAt('07:00')->timezone('America/Merida');

// Respaldo diario de la base en horario de baja actividad (ver docs/RESPALDO.md)
Schedule::command('crea:respaldo-base')->dailyAt('03:00')->timezone('America/Merida');

// A las 4:30 se borran bitácoras viejas y a las 6:00 se revisa que el respaldo haya quedado (docs/RESPALDO.md).
// La revisión exige un respaldo de las últimas 26 h: a las 6:00 el de ayer ya pasó ese plazo, así que si hoy
// no se generó el aviso sale el mismo día; a las 4:00 tendría solo 25 h y el aviso llegaría un día tarde.
// Corren dentro de una función para que lo que reporta el comando quede en la bitácora y, si falla,
// también en la alerta a los administradores: con Schedule::command solo llegaría "exit code 1".
$conResultado = fn (string $comando) => function () use ($comando) {
    $codigo    = Artisan::call($comando);
    $resultado = trim(Artisan::output());

    BitacoraTareaProgramada::anotarResultado($comando, $resultado);

    if ($codigo !== 0) {
        throw new RuntimeException($resultado);
    }
};

Schedule::call($conResultado('crea:verificar-respaldo'))->name('crea:verificar-respaldo')->dailyAt('06:00')->timezone('America/Merida');
Schedule::call($conResultado('crea:limpiar-bitacoras'))->name('crea:limpiar-bitacoras')->dailyAt('04:30')->timezone('America/Merida');

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
