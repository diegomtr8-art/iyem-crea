<?php

use App\Models\BitacoraTareaProgramada;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CacheEventMutex;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/** Datos de una conexión MySQL de prueba; las pruebas corren en SQLite y por eso se pasa --conexion=mysql. */
function usarMysqlParaElRespaldo(): void
{
    config([
        'database.connections.mysql.database' => 'crea_prueba',
        'database.connections.mysql.username' => 'crea_usuario',
        'database.connections.mysql.password' => 'Cl4ve"#\Segura',
    ]);
}

/** Simula mysqldump: escribe el volcado donde lo pide el comando y deja ver con qué se llamó. */
function simularMysqldump(?Closure $alLlamar = null, string $volcado = "-- MySQL dump\nINSERT INTO `creditos` VALUES (1,'CREA-001');\n-- Dump completed on 2026-10-05  3:00:01\n"): void
{
    Process::fake(function (PendingProcess $proceso) use ($alLlamar, $volcado) {
        $opcion = fn (string $nombre) => Str::after(collect($proceso->command)->first(fn ($a) => str_starts_with($a, "--{$nombre}=")), '=');

        if ($alLlamar) {
            $alLlamar($proceso->command, File::get($opcion('defaults-extra-file')));
        }

        File::put($opcion('result-file'), $volcado);

        return Process::result();
    });
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-05 09:00', 'UTC')); // 03:00 en Mérida
    $this->directorio = storage_path('framework/testing/respaldos');
    File::deleteDirectory($this->directorio);
    config(['respaldo.directorio' => $this->directorio]);
});

afterEach(fn () => File::deleteDirectory($this->directorio));

test('genera un volcado comprimido con la fecha en el nombre', function () {
    usarMysqlParaElRespaldo();
    simularMysqldump();

    $this->artisan('crea:respaldo-base', ['--conexion' => 'mysql'])->assertSuccessful();

    $respaldo = "{$this->directorio}/crea-respaldo-2026-10-05_030000.sql.gz";

    expect(File::exists($respaldo))->toBeTrue()
        ->and(gzdecode(File::get($respaldo)))->toContain("INSERT INTO `creditos` VALUES (1,'CREA-001');")
        // No se quedan ni el volcado sin comprimir ni el archivo con la contraseña
        ->and(File::files($this->directorio))->toHaveCount(1)
        ->and(File::get("{$this->directorio}/.htaccess"))->toContain('Require all denied');
});

test('la contraseña de la base no va en la línea de comandos', function () {
    usarMysqlParaElRespaldo();
    simularMysqldump(function (array $argumentos, string $credenciales) use (&$llamada) {
        $llamada = compact('argumentos', 'credenciales');
    });

    $this->artisan('crea:respaldo-base', ['--conexion' => 'mysql'])->assertSuccessful();

    expect(implode(' ', $llamada['argumentos']))->not->toContain('Cl4ve')
        ->and(end($llamada['argumentos']))->toBe('crea_prueba')
        ->and($llamada['credenciales'])->toContain('user="crea_usuario"')
        // La comilla y la diagonal van escapadas; si no, mysqldump cortaría la contraseña en el #
        ->and($llamada['credenciales'])->toContain('password="Cl4ve\"#\\\\Segura"');
});

test('borra los respaldos de más de 30 días y respeta los demás archivos', function () {
    usarMysqlParaElRespaldo();
    simularMysqldump();
    File::ensureDirectoryExists($this->directorio);

    foreach (['crea-respaldo-2026-09-04_030000.sql.gz', 'crea-respaldo-2026-09-05_030000.sql.gz', 'notas.txt'] as $archivo) {
        File::put("{$this->directorio}/{$archivo}", 'x');
    }

    $this->artisan('crea:respaldo-base', ['--conexion' => 'mysql'])->assertSuccessful();

    expect(collect(File::files($this->directorio))->map->getFilename()->sort()->values()->all())->toBe([
        'crea-respaldo-2026-09-05_030000.sql.gz',
        'crea-respaldo-2026-10-05_030000.sql.gz',
        'notas.txt',
    ]);
});

test('si mysqldump falla termina con error, lo anota en el log y no borra los respaldos anteriores', function () {
    usarMysqlParaElRespaldo();
    Process::fake(fn () => Process::result(errorOutput: "mysqldump: Got error: 1045: Access denied for user 'crea_usuario'", exitCode: 2));
    Log::spy();
    File::ensureDirectoryExists($this->directorio);
    File::put("{$this->directorio}/crea-respaldo-2026-08-01_030000.sql.gz", 'x');

    $this->artisan('crea:respaldo-base', ['--conexion' => 'mysql'])->assertFailed();

    Log::shouldHaveReceived('error')->once()
        ->withArgs(fn ($mensaje, $contexto) => str_contains($contexto['error'], 'Access denied'));

    expect(collect(File::files($this->directorio))->map->getFilename()->all())
        ->toBe(['crea-respaldo-2026-08-01_030000.sql.gz']);
});

test('un volcado que se cortó a la mitad no se da por bueno', function () {
    usarMysqlParaElRespaldo();
    simularMysqldump(volcado: "-- MySQL dump\nINSERT INTO `creditos` VALUES (1,");

    $this->artisan('crea:respaldo-base', ['--conexion' => 'mysql'])->assertFailed();

    expect(File::files($this->directorio))->toBeEmpty();
});

test('solo respalda bases MySQL o MariaDB', function () {
    Process::fake();

    $this->artisan('crea:respaldo-base')
        ->expectsOutputToContain('Solo se respaldan bases MySQL/MariaDB')
        ->assertFailed();

    Process::assertNothingRan();
});

test('está programado a diario a las 3:00 de Mérida', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command ?? '', 'crea:respaldo-base'));

    expect($evento)->not->toBeNull()
        ->and($evento->expression)->toBe('0 3 * * *')
        ->and($evento->timezone)->toBe('America/Merida');
});

test('si el respaldo programado falla queda en la bitácora, en el panel y avisa a los administradores', function () {
    $this->withoutVite();
    Role::create(['name' => 'Administrador']);
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');

    // Antes de la primera ejecución el panel ya lo muestra como pendiente
    $this->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tareas', fn ($tareas) => collect($tareas)
            ->contains(fn ($t) => $t['tarea'] === 'crea:respaldo-base' && $t['sin_correr'])));

    // Así llegan los eventos cuando el comando termina con código de salida 1
    $tarea = new Event(app(CacheEventMutex::class), Artisan::formatCommandString('crea:respaldo-base'));
    $tarea->exitCode = 1;

    event(new ScheduledTaskStarting($tarea));
    event(new ScheduledTaskFinished($tarea, 2.5));
    event(new ScheduledTaskFailed($tarea, new RuntimeException('Scheduled command failed with exit code [1].')));

    expect(BitacoraTareaProgramada::where('tarea', 'crea:respaldo-base')->value('estado'))->toBe('error')
        ->and($admin->unreadNotifications)->toHaveCount(1);

    $this->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tareas', fn ($tareas) => collect($tareas)
            ->contains(fn ($t) => $t['tarea'] === 'crea:respaldo-base' && $t['ultimo_estado'] === 'error')));
});
