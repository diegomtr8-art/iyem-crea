<?php

use App\Jobs\EnviarRecordatorioPago;
use App\Mail\RecordatorioPagoMail;
use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\BitacoraTareaProgramada;
use App\Models\ModalidadCrea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/** Crédito de prueba con una cuota de $1,250.50 que vence en 3 días (Mérida). */
function cuotaQueVenceEnTresDias(): Amortizacion
{
    $modalidad  = ModalidadCrea::create(['nombre' => 'Modalidad de prueba', 'tasa_interes' => 0, 'tasa_moratoria' => 0]);
    $acreditado = Acreditado::create(['nombre_completo' => 'Ana Prueba Ejemplo', 'municipio' => 'Mérida', 'correo' => 'ana@prueba.test']);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'CREA-PRUEBA-001',
        'monto_otorgado'         => 6000,
        'plazo_meses'            => 6,
        'fecha_entrega'          => Carbon::today('America/Merida')->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => 'Activo',
    ]);

    return $credito->amortizaciones()->create([
        'numero_cuota'               => 2,
        'fecha_vencimiento'          => Carbon::today('America/Merida')->addDays(3)->toDateString(),
        'saldo_insoluto'             => 6000,
        'capital_esperado'           => 1250.50,
        'interes_ordinario_esperado' => 0,
        'cuota_fija'                 => 1250.50,
        'pago_restante'              => 1250.50,
        'estado'                     => 'Pendiente',
    ]);
}

function eventoProgramado(string $nombre): ?Illuminate\Console\Scheduling\Event
{
    return collect(app(Schedule::class)->events())
        ->first(fn ($e) => $e->description === $nombre || str_ends_with((string) $e->command, $nombre));
}

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-05 18:00', 'UTC')); // 12:00 en Mérida: no coincide con las tareas diarias
    Role::create(['name' => 'Administrador']);
});

test('los comandos diarios están programados en su horario de Mérida', function (string $comando, string $expresion) {
    $evento = eventoProgramado($comando);

    expect($evento)->not->toBeNull()
        ->and($evento->expression)->toBe($expresion)
        ->and($evento->timezone)->toBe('America/Merida');
})->with([
    'mora a las 8:00'          => ['crea:update-moratorio', '0 8 * * *'],
    'recordatorios a las 9:00' => ['crea:recordatorios-pago', '0 9 * * *'],
]);

test('la cola se procesa desde el mismo cron cada minuto, solo cuando hay correos pendientes', function () {
    config(['queue.default' => 'database']);
    $evento = eventoProgramado('procesar-cola');

    expect($evento->expression)->toBe('* * * * *')
        ->and($evento->filtersPass(app()))->toBeFalse();

    EnviarRecordatorioPago::dispatch(cuotaQueVenceEnTresDias());

    expect($evento->filtersPass(app()))->toBeTrue();
});

test('el recordatorio diario se encola y el scheduler lo envía en la siguiente vuelta', function () {
    config(['queue.default' => 'database']);
    Mail::fake();
    cuotaQueVenceEnTresDias();

    $this->artisan('crea:recordatorios-pago')->expectsOutput('Recordatorios encolados: 1');
    Mail::assertNothingSent();

    $this->artisan('schedule:run');

    Mail::assertSent(RecordatorioPagoMail::class, fn ($m) => $m->hasTo('ana@prueba.test'));
    expect(DB::table('jobs')->count())->toBe(0)
        ->and(BitacoraTareaProgramada::where('tarea', 'procesar-cola')->value('estado'))->toBe('exito');
});

test('si un correo agota sus 3 intentos queda en la bitácora y avisa a los administradores', function () {
    config(['queue.default' => 'database']);
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');
    $cuota = cuotaQueVenceEnTresDias();
    DB::table('amortizaciones')->where('id', $cuota->id)->update(['recordatorio_enviado_at' => now()]);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('Connection to smtp.hostinger.com:465 timed out'));

    EnviarRecordatorioPago::dispatch($cuota);

    // Intento 1 ahora, el 2 un minuto después y el 3 cinco minutos después
    $this->artisan('schedule:run');
    $this->travel(61)->seconds();
    $this->artisan('schedule:run');

    expect(BitacoraTareaProgramada::where('estado', 'error')->count())->toBe(0);

    $this->travel(301)->seconds();
    $this->artisan('schedule:run');

    expect(BitacoraTareaProgramada::where('tarea', 'procesar-cola')->where('estado', 'error')->value('mensaje_error'))
        ->toContain('1 correo(s) no se pudieron enviar después de 3 intentos')
        ->and($admin->unreadNotifications)->toHaveCount(1)
        ->and(DB::table('failed_jobs')->count())->toBe(1)
        // Se quita la marca para poder reenviarlo con el botón
        ->and($cuota->fresh()->recordatorio_enviado_at)->toBeNull();
});

test('procesar-cola no se marca como "sin correr" en el panel cuando no hay correos', function () {
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');
    BitacoraTareaProgramada::create(['tarea' => 'procesar-cola', 'inicio' => now()->subDays(3), 'fin' => now()->subDays(3), 'estado' => 'exito']);

    $this->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tareas', function ($tareas) {
            $tareas = collect($tareas)->keyBy('tarea');

            return $tareas['procesar-cola']['sin_correr'] === false
                && $tareas['crea:update-moratorio']['sin_correr'] === true;
        }));
});

test('el correo trae nombre, monto, fecha y cómo contactar al IYEM, sin enlaces inventados', function () {
    $correo = RecordatorioPagoMail::desdeCuota(cuotaQueVenceEnTresDias()->load('credito.acreditado'));

    $correo->assertHasSubject('Recordatorio de pago CREA: tu cuota 2 vence el 08/10/2026')
        ->assertSeeInHtml('Ana Prueba Ejemplo')
        ->assertSeeInHtml('CREA-PRUEBA-001')
        ->assertSeeInHtml('$1,250.50 MXN')
        ->assertSeeInHtml('08/10/2026')
        ->assertSeeInHtml('crea@iyemyucatan.com')
        ->assertSeeInHtml('999 941 2170');

    // Los únicos enlaces son el sitio (APP_URL) y el correo del programa
    preg_match_all('/href="([^"]+)"/', $correo->render(), $enlaces);
    expect($enlaces[1])->toBe([url('/'), 'mailto:crea@iyemyucatan.com']);
});
