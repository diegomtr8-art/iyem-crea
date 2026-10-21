<?php

use App\Models\BitacoraTareaProgramada;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/** Deja un archivo en la carpeta de respaldos con el tamaño y la hora de modificación indicados (hora de Mérida). */
function archivoDeRespaldo(string $nombre, int $bytes, string $fecha): void
{
    $ruta = config('respaldo.directorio') . DIRECTORY_SEPARATOR . $nombre;

    File::ensureDirectoryExists(dirname($ruta));
    File::put($ruta, str_repeat('x', $bytes));
    touch($ruta, Carbon::parse($fecha, 'America/Merida')->getTimestamp());
    clearstatcache();
}

function administradorDeRespaldos(): User
{
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');

    return $admin;
}

function verificacionProgramada(): ?BitacoraTareaProgramada
{
    return BitacoraTareaProgramada::where('tarea', 'crea:verificar-respaldo')->latest('id')->first();
}

/**
 * schedule:run con Artisan::call y no con $this->artisan(): este último simula la salida de la consola
 * y el comando que corre adentro ya no deja su texto en Artisan::output(), que es lo que llega a la bitácora.
 */
function correrScheduler(): void
{
    Artisan::call('schedule:run');
}

beforeEach(function () {
    $this->withoutVite();
    Role::create(['name' => 'Administrador']);
    $this->directorio = storage_path('framework/testing/respaldos-verificacion');
    File::deleteDirectory($this->directorio);
    config(['respaldo.directorio' => $this->directorio]);
    $this->travelTo(Carbon::parse('2026-10-14 12:00', 'UTC')); // 06:00 en Mérida, la hora de la verificación
});

afterEach(fn () => File::deleteDirectory($this->directorio));

test('la verificación está programada a diario a las 6:00 de Mérida, tres horas después del respaldo', function () {
    $eventos = collect(app(Schedule::class)->events());
    $verificacion = $eventos->first(fn ($e) => $e->description === 'crea:verificar-respaldo');
    $respaldo     = $eventos->first(fn ($e) => str_ends_with((string) $e->command, 'crea:respaldo-base'));

    expect($verificacion->expression)->toBe('0 6 * * *')
        ->and($verificacion->timezone)->toBe('America/Merida')
        ->and($respaldo->expression)->toBe('0 3 * * *');
});

test('con un respaldo de las últimas 26 h no hay alerta y la bitácora dice OK', function (int $bytes, string $fecha, string $resultado) {
    archivoDeRespaldo('crea-respaldo-2026-10-14_030000.sql.gz', $bytes, $fecha);
    $admin = administradorDeRespaldos();

    correrScheduler();

    expect(verificacionProgramada()->estado)->toBe('exito')
        ->and(verificacionProgramada()->resultado)->toBe($resultado)
        ->and($admin->unreadNotifications)->toHaveCount(0);

    // El panel muestra el resultado debajo de "Exitosa"
    $this->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tareas', fn ($tareas) => collect($tareas)
            ->firstWhere('tarea', 'crea:verificar-respaldo')['ultimo_mensaje'] === $resultado));
})->with([
    'el de hoy a las 3:00'     => [422195, '2026-10-14 03:00', 'OK: crea-respaldo-2026-10-14_030000.sql.gz (412.3 KB), del 14/10/2026 03:00.'],
    'justo el mínimo de 10 KB' => [10240, '2026-10-14 03:00', 'OK: crea-respaldo-2026-10-14_030000.sql.gz (10 KB), del 14/10/2026 03:00.'],
    'hace 25 h 59 min'         => [422195, '2026-10-13 04:01', 'OK: crea-respaldo-2026-10-14_030000.sql.gz (412.3 KB), del 13/10/2026 04:01.'],
]);

test('si mañana no se genera el respaldo, el administrador recibe el aviso ese mismo día a las 6:00', function () {
    archivoDeRespaldo('crea-respaldo-2026-10-14_030000.sql.gz', 422195, '2026-10-14 03:00');
    $admin = administradorDeRespaldos();

    correrScheduler();
    expect($admin->unreadNotifications)->toHaveCount(0);

    // Al día siguiente el respaldo de las 3:00 no se generó
    $this->travelTo(Carbon::parse('2026-10-15 12:00', 'UTC')); // 06:00 en Mérida
    correrScheduler();

    $mensaje = 'Falta respaldo: no hay respaldo de las últimas 26 h. Último respaldo: crea-respaldo-2026-10-14_030000.sql.gz (412.3 KB), del 14/10/2026 03:00.';

    expect(verificacionProgramada()->estado)->toBe('error')
        ->and(verificacionProgramada()->mensaje_error)->toBe($mensaje)
        ->and(verificacionProgramada()->resultado)->toBe($mensaje)
        ->and($admin->fresh()->unreadNotifications)->toHaveCount(1)
        ->and($admin->fresh()->unreadNotifications->first()->data)->toMatchArray([
            'tarea'   => 'crea:verificar-respaldo',
            'mensaje' => $mensaje,
        ]);
});

test('un respaldo de hace más de 26 h ya no cuenta', function () {
    archivoDeRespaldo('crea-respaldo-2026-10-13_030000.sql.gz', 422195, '2026-10-13 03:59'); // 26 h 1 min antes de las 6:00
    $admin = administradorDeRespaldos();

    correrScheduler();

    expect(verificacionProgramada()->mensaje_error)->toBe(
        'Falta respaldo: no hay respaldo de las últimas 26 h. Último respaldo: crea-respaldo-2026-10-13_030000.sql.gz (412.3 KB), del 13/10/2026 03:59.'
    )
        ->and($admin->unreadNotifications)->toHaveCount(1);
});

test('sin ningún respaldo con el nombre de crea:respaldo-base también avisa', function (Closure $preparar) {
    $preparar();
    $admin = administradorDeRespaldos();

    correrScheduler();

    expect(verificacionProgramada()->estado)->toBe('error')
        ->and(verificacionProgramada()->mensaje_error)->toBe('Falta respaldo: no hay ningún archivo de respaldo en la carpeta.')
        ->and($admin->unreadNotifications)->toHaveCount(1);
})->with([
    'la carpeta no existe' => [fn () => null],
    'la carpeta está vacía' => [fn () => File::ensureDirectoryExists(config('respaldo.directorio'))],
    // Un respaldo a medio comprimir, una copia a mano y el .htaccess de la carpeta no cuentan
    'solo archivos con otro nombre' => [function () {
        archivoDeRespaldo('crea-respaldo-2026-10-14_030000.sql.gz.part', 422195, '2026-10-14 03:00');
        archivoDeRespaldo('respaldo-manual-2026-10-14.sql.gz', 422195, '2026-10-14 03:00');
        archivoDeRespaldo('.htaccess', 122, '2026-10-14 03:00');
    }],
]);

test('un respaldo vacío o demasiado chico también avisa', function (int $bytes, string $kb) {
    archivoDeRespaldo('crea-respaldo-2026-10-14_030000.sql.gz', $bytes, '2026-10-14 03:00');
    $admin = administradorDeRespaldos();

    correrScheduler();

    expect(verificacionProgramada()->estado)->toBe('error')
        ->and(verificacionProgramada()->mensaje_error)->toBe(
            "Falta respaldo: el archivo más reciente pesa {$kb} KB y el mínimo es 10 KB. Último respaldo: crea-respaldo-2026-10-14_030000.sql.gz ({$kb} KB), del 14/10/2026 03:00."
        )
        ->and($admin->unreadNotifications)->toHaveCount(1);
})->with([
    'tamaño cero'           => [0, '0'],
    'por debajo del mínimo' => [7577, '7.4'], // lo que pesa la base vacía, solo con la estructura
]);

test('el panel muestra la fecha y el tamaño del último respaldo', function () {
    $admin = administradorDeRespaldos();
    archivoDeRespaldo('crea-respaldo-2026-10-13_030000.sql.gz', 409600, '2026-10-13 03:00');
    archivoDeRespaldo('crea-respaldo-2026-10-14_030000.sql.gz', 422195, '2026-10-14 03:00');

    $this->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('respaldo.problema', null)
            ->where('respaldo.ultimo', ['archivo' => 'crea-respaldo-2026-10-14_030000.sql.gz', 'fecha' => '14/10/2026 03:00', 'kb' => 412.3])
            // Antes de su primera ejecución, las tareas nuevas ya salen como pendientes
            ->where('tareas', fn ($tareas) => collect($tareas)->whereIn('tarea', ['crea:verificar-respaldo', 'crea:limpiar-bitacoras'])
                ->where('sin_correr', true)->count() === 2));
});

test('el panel avisa aunque el cron no haya corrido, porque revisa la carpeta al abrirlo', function () {
    $admin = administradorDeRespaldos();
    archivoDeRespaldo('crea-respaldo-2026-10-12_030000.sql.gz', 422195, '2026-10-12 03:00');

    $this->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('respaldo.problema', 'no hay respaldo de las últimas 26 h')
            ->where('respaldo.ultimo.fecha', '12/10/2026 03:00'));
});

test('corrida a mano muestra el resultado en la terminal y no se anota en el panel', function () {
    $this->artisan('crea:verificar-respaldo')
        ->expectsOutput('Falta respaldo: no hay ningún archivo de respaldo en la carpeta.')
        ->assertFailed();

    archivoDeRespaldo('crea-respaldo-2026-10-14_030000.sql.gz', 422195, '2026-10-14 03:00');

    $this->artisan('crea:verificar-respaldo')
        ->expectsOutput('OK: crea-respaldo-2026-10-14_030000.sql.gz (412.3 KB), del 14/10/2026 03:00.')
        ->assertSuccessful();

    expect(BitacoraTareaProgramada::count())->toBe(0);
});
