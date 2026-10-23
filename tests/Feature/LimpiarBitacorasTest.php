<?php

use App\Models\BitacoraTareaProgramada;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Filas justo antes y justo después de cada fecha de corte, más auditoría muy vieja que nunca se borra.
 * Hoy es 14/10/2026 4:30 de Mérida.
 */
function filasParaLimpiar(): void
{
    $tarea = fn (Carbon $inicio) => BitacoraTareaProgramada::create(['tarea' => 'crea:update-moratorio', 'inicio' => $inicio, 'fin' => $inicio, 'estado' => 'exito']);
    $tarea(now()->subDays(90)->subMinute());   // se borra
    $tarea(now()->subDays(90)->addMinute());   // se queda
    $tarea(now()->subDay());                   // se queda

    $fallo = fn (Carbon $fecha) => DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default',
        'payload' => '{}', 'exception' => 'Error de prueba', 'failed_at' => $fecha,
    ]);
    $fallo(now()->subDays(30)->subMinute());   // se borra
    $fallo(now()->subDays(30)->addMinute());   // se queda
    $fallo(now()->subHour());                  // se queda

    DB::table('cache')->insert([
        ['key' => 'vencida', 'value' => 'x', 'expiration' => now()->subSecond()->timestamp],   // se borra
        ['key' => 'vigente', 'value' => 'x', 'expiration' => now()->addHour()->timestamp],     // se queda
    ]);

    foreach ([now()->subYears(3), now()->subDay()] as $fecha) {
        DB::table('auditoria_log')->insert([
            'entidad' => 'Pago', 'entidad_id' => 1, 'accion' => 'created',
            'descripcion' => 'Pago de prueba', 'created_at' => $fecha, 'updated_at' => $fecha,
        ]);
    }
}

function conteoDeTablas(): array
{
    return collect(['bitacora_tareas_programadas', 'failed_jobs', 'cache', 'auditoria_log'])
        ->mapWithKeys(fn ($tabla) => [$tabla => DB::table($tabla)->count()])
        ->all();
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-14 10:30', 'UTC')); // 04:30 en Mérida
});

test('con --dry-run solo cuenta lo que borraría y no borra nada', function () {
    filasParaLimpiar();

    $this->artisan('crea:limpiar-bitacoras', ['--dry-run' => true])
        ->expectsOutput('Modo prueba, no se borró nada. Se borrarían bitacora_tareas_programadas: 1 (más de 90 días), failed_jobs: 1 (más de 30 días), cache: 1 (vencidas).')
        ->assertSuccessful();

    expect(conteoDeTablas())->toBe(['bitacora_tareas_programadas' => 3, 'failed_jobs' => 3, 'cache' => 2, 'auditoria_log' => 2]);
});

test('borra solo lo anterior a la fecha de corte y nunca toca auditoria_log', function () {
    filasParaLimpiar();
    Log::spy();

    $this->artisan('crea:limpiar-bitacoras')
        ->expectsOutput('Se borraron bitacora_tareas_programadas: 1 (más de 90 días), failed_jobs: 1 (más de 30 días), cache: 1 (vencidas).')
        ->assertSuccessful();

    expect(conteoDeTablas())->toBe(['bitacora_tareas_programadas' => 2, 'failed_jobs' => 2, 'cache' => 1, 'auditoria_log' => 2])
        ->and(BitacoraTareaProgramada::where('inicio', '<', now()->subDays(90))->count())->toBe(0)
        ->and(DB::table('failed_jobs')->where('failed_at', '<', now()->subDays(30))->count())->toBe(0)
        ->and(DB::table('cache')->pluck('key')->all())->toBe(['vigente']);

    // Corrida a mano: no va al panel, pero queda en el log
    Log::shouldHaveReceived('info')->once()
        ->with('crea:limpiar-bitacoras. Se borraron bitacora_tareas_programadas: 1 (más de 90 días), failed_jobs: 1 (más de 30 días), cache: 1 (vencidas).');
});

test('una segunda corrida ya no encuentra nada que borrar', function () {
    filasParaLimpiar();

    $this->artisan('crea:limpiar-bitacoras')->assertSuccessful();

    $this->artisan('crea:limpiar-bitacoras', ['--dry-run' => true])
        ->expectsOutput('Modo prueba, no se borró nada. Se borrarían bitacora_tareas_programadas: 0 (más de 90 días), failed_jobs: 0 (más de 30 días), cache: 0 (vencidas).');
});

test('está programada a diario a las 4:30 y deja en la bitácora cuántas filas borró', function () {
    filasParaLimpiar();
    $evento = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'crea:limpiar-bitacoras');

    expect($evento->expression)->toBe('30 4 * * *')
        ->and($evento->timezone)->toBe('America/Merida');

    // Artisan::call y no $this->artisan(), por lo mismo que en VerificarRespaldoTest
    Artisan::call('schedule:run');

    $registros = BitacoraTareaProgramada::where('tarea', 'crea:limpiar-bitacoras')->get();

    expect($registros)->toHaveCount(1)
        ->and($registros->first()->estado)->toBe('exito')
        ->and($registros->first()->resultado)->toBe('Se borraron bitacora_tareas_programadas: 1 (más de 90 días), failed_jobs: 1 (más de 30 días), cache: 1 (vencidas).')
        ->and(DB::table('auditoria_log')->count())->toBe(2);
});

test('los plazos salen de config/limpieza.php', function () {
    filasParaLimpiar();
    config(['limpieza.bitacora_tareas_dias' => 7, 'limpieza.correos_fallidos_dias' => 2]);

    $this->artisan('crea:limpiar-bitacoras', ['--dry-run' => true])
        ->expectsOutput('Modo prueba, no se borró nada. Se borrarían bitacora_tareas_programadas: 2 (más de 7 días), failed_jobs: 2 (más de 2 días), cache: 1 (vencidas).')
        ->assertSuccessful();

    $this->artisan('crea:limpiar-bitacoras')->assertSuccessful();

    // Solo quedan las filas de ayer y de hace una hora, y la auditoría completa
    expect(conteoDeTablas())->toBe(['bitacora_tareas_programadas' => 1, 'failed_jobs' => 1, 'cache' => 1, 'auditoria_log' => 2])
        ->and(BitacoraTareaProgramada::where('inicio', '<', now()->subDays(7))->count())->toBe(0)
        ->and(DB::table('failed_jobs')->where('failed_at', '<', now()->subDays(2))->count())->toBe(0);
});

test('los plazos se cambian desde el .env y por omisión son 90 y 30 días', function () {
    expect(require config_path('limpieza.php'))->toBe(['bitacora_tareas_dias' => 90, 'correos_fallidos_dias' => 30]);

    putenv('LIMPIEZA_BITACORA_TAREAS_DIAS=120');
    putenv('LIMPIEZA_CORREOS_FALLIDOS_DIAS=15');

    try {
        expect(require config_path('limpieza.php'))->toBe(['bitacora_tareas_dias' => 120, 'correos_fallidos_dias' => 15]);
    } finally {
        putenv('LIMPIEZA_BITACORA_TAREAS_DIAS');
        putenv('LIMPIEZA_CORREOS_FALLIDOS_DIAS');
    }
});

test('con un plazo menor a 1 día o que no es número no borra nada y falla', function (string $clave, mixed $valor, string $mensaje) {
    filasParaLimpiar();
    config([$clave => $valor]);

    $this->artisan('crea:limpiar-bitacoras')
        ->expectsOutput($mensaje)
        ->assertFailed();

    expect(conteoDeTablas())->toBe(['bitacora_tareas_programadas' => 3, 'failed_jobs' => 3, 'cache' => 2, 'auditoria_log' => 2]);
})->with([
    'bitácora en 0'          => ['limpieza.bitacora_tareas_dias', 0, 'El plazo de bitacora_tareas_programadas debe ser de al menos 1 día y está en «0». Revisa LIMPIEZA_BITACORA_TAREAS_DIAS en el .env. No se borró nada.'],
    'bitácora negativa'      => ['limpieza.bitacora_tareas_dias', -5, 'El plazo de bitacora_tareas_programadas debe ser de al menos 1 día y está en «-5». Revisa LIMPIEZA_BITACORA_TAREAS_DIAS en el .env. No se borró nada.'],
    'correos fallidos texto' => ['limpieza.correos_fallidos_dias', 'treinta', 'El plazo de failed_jobs debe ser de al menos 1 día y está en «treinta». Revisa LIMPIEZA_CORREOS_FALLIDOS_DIAS en el .env. No se borró nada.'],
]);

test('si el plazo está mal configurado, la corrida programada queda en error y avisa a los administradores', function () {
    filasParaLimpiar();
    config(['limpieza.correos_fallidos_dias' => 0]);
    Role::create(['name' => 'Administrador']);
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');

    Artisan::call('schedule:run');

    $registro = BitacoraTareaProgramada::where('tarea', 'crea:limpiar-bitacoras')->first();

    expect($registro->estado)->toBe('error')
        ->and($registro->mensaje_error)->toBe('El plazo de failed_jobs debe ser de al menos 1 día y está en «0». Revisa LIMPIEZA_CORREOS_FALLIDOS_DIAS en el .env. No se borró nada.')
        ->and($admin->fresh()->unreadNotifications)->toHaveCount(1)
        ->and(DB::table('failed_jobs')->count())->toBe(3);
});
