<?php

use App\Jobs\EnviarRecordatorioPago;
use App\Mail\RecordatorioPagoMail;
use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\ModalidadCrea;
use App\Models\User;
use App\Services\CorreosFallidosService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Cuota que vence en 3 días cuyo recordatorio falló 3 veces, como pasaría con el
 * servidor de correo caído: el job se encola igual que con el botón y termina en failed_jobs.
 * Datos ficticios.
 */
function cuotaConRecordatorioFallido(string $clave = 'CREA-PRUEBA-001', string $correo = 'ana.prueba@ejemplo.com'): Amortizacion
{
    $modalidad = ModalidadCrea::create(['nombre' => 'Emprendedores-' . uniqid(), 'tasa_interes' => 7, 'tasa_moratoria' => 17.5]);
    $acreditado = Acreditado::create(['nombre_completo' => 'Ana Prueba Ejemplo', 'municipio' => 'Mérida', 'correo' => $correo]);
    $credito = $acreditado->creditos()->create([
        'modalidad_id' => $modalidad->id, 'clave_contrato' => $clave, 'monto_otorgado' => 12000, 'plazo_meses' => 12,
        'fecha_entrega' => '2026-09-01', 'tasa_interes_ordinario' => 7, 'tasa_interes_moratorio' => 17.5, 'estatus' => 'Activo',
    ]);
    $cuota = $credito->amortizaciones()->create([
        'numero_cuota' => 1, 'fecha_vencimiento' => Carbon::now('America/Merida')->addDays(3)->toDateString(),
        'saldo_insoluto' => 12000, 'capital_esperado' => 1000, 'interes_ordinario_esperado' => 70, 'cuota_fija' => 1070,
        'pago_restante' => 1070, 'estado' => 'Pendiente',
    ]);

    DB::table('amortizaciones')->where('id', $cuota->id)->update(['recordatorio_enviado_at' => now()]);
    EnviarRecordatorioPago::dispatch($cuota);

    // Servidor de correo que rechaza al destinatario, con el mismo error que da un SMTP real
    config(['mail.default' => 'rechaza', 'mail.mailers.rechaza' => ['transport' => 'rechaza']]);
    Mail::extend('rechaza', fn () => new class extends AbstractTransport {
        protected function doSend(SentMessage $message): void
        {
            $destino = $message->getEnvelope()->getRecipients()[0]->getAddress();
            throw new TransportException("Expected response code \"250\" but got code \"550\", with message \"550 5.1.1 <{$destino}>: Recipient address rejected\".");
        }

        public function __toString(): string
        {
            return 'rechaza://';
        }
    });
    // Se avanza el reloj entre intentos por si el job espera antes de reintentar
    foreach ([0, 61, 301] as $segundos) {
        test()->travel($segundos)->seconds();
        Artisan::call('queue:work', ['--stop-when-empty' => true, '--sleep' => 0]);
    }

    return $cuota->fresh();
}

function administradorDeCorreos(): User
{
    $admin = User::factory()->create(['tipo' => 'operativo', 'name' => 'Admin Prueba']);
    $admin->assignRole('Administrador');

    return $admin;
}

function enviarCola(): void
{
    Artisan::call('queue:work', ['--stop-when-empty' => true, '--sleep' => 0]);
}

beforeEach(function () {
    Role::create(['name' => 'Administrador']);
    config(['queue.default' => 'database']);
    $this->travelTo(Carbon::parse('2026-10-05 18:00', 'UTC')); // 12:00 en Mérida
});

test('la pantalla lista el fallo con el correo enmascarado y sin el payload', function () {
    cuotaConRecordatorioFallido();

    $respuesta = $this->withoutVite()->actingAs(administradorDeCorreos())->get(route('correos-fallidos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/CorreosFallidos')
            ->has('fallos', 1)
            ->where('fallos.0.tipo', 'Recordatorio de pago')
            ->where('fallos.0.destinatario', 'a***@ejemplo.com')
            ->where('fallos.0.credito', 'CREA-PRUEBA-001')
            ->where('fallos.0.cuota', 1)
            ->where('fallos.0.vence', '08/10/2026')
            ->where('fallos.0.fecha_fallo', fn ($fecha) => str_starts_with($fecha, '05/10/2026 12:0'))
            ->where('fallos.0.error', 'TransportException: Expected response code "250" but got code "550", with message "550 5.1.1 <a***@ejemplo.com>: Recipient address rejected".')
            ->where('fallos.0.no_aplica', null)
            ->missing('fallos.0.payload'));

    // El correo completo no sale en ninguna parte de la página, ni dentro del mensaje de error
    expect($respuesta->getContent())->not->toContain('ana.prueba@ejemplo.com');
});

test('el error se resume sin la ruta del archivo', function (string $excepcion, string $resumen) {
    expect(CorreosFallidosService::resumirError($excepcion))->toBe($resumen);
})->with([
    'ruta de Windows con espacios' => [
        'Symfony\Component\Mailer\Exception\TransportException: Connection could not be established in C:\Users\ana\Mis Documentos\iyem-crea\vendor\SocketStream.php:154' . "\nStack trace:\n#0 {main}",
        'TransportException: Connection could not be established',
    ],
    'ruta de Linux' => [
        'Symfony\Component\Mailer\Exception\TransportException: Expected response code "250" but got code "550" in /home/u123/public_html/vendor/AbstractStream.php:342' . "\nStack trace:",
        'TransportException: Expected response code "250" but got code "550"',
    ],
    'mensaje que dice "in"' => [
        'RuntimeException: Failed in step 2 in /var/www/app/Job.php:10',
        'RuntimeException: Failed in step 2',
    ],
]);

test('reintentar vuelve a encolar el recordatorio, lo envía y deja registro de quién fue', function () {
    $cuota = cuotaConRecordatorioFallido();
    expect($cuota->recordatorio_enviado_at)->toBeNull(); // el job quitó la marca al fallar

    $admin = administradorDeCorreos();
    $id = DB::table('failed_jobs')->value('id');
    Mail::fake();

    $this->actingAs($admin)->post(route('correos-fallidos.reintentar', $id))
        ->assertSessionHas('success', 'Se volvió a poner en la cola de envío el recordatorio de pago de la cuota 1 del crédito CREA-PRUEBA-001.');

    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and($cuota->fresh()->recordatorio_enviado_at)->not->toBeNull();

    enviarCola();

    Mail::assertSent(RecordatorioPagoMail::class, 1);
    Mail::assertSent(RecordatorioPagoMail::class, fn ($m) => $m->hasTo('ana.prueba@ejemplo.com') && $m->contrato === 'CREA-PRUEBA-001');
    $this->assertDatabaseHas('auditoria_log', [
        'entidad' => 'CorreoFallido', 'entidad_id' => $id, 'accion' => 'updated', 'campo_modificado' => 'estado',
        'valor_anterior' => 'Fallido', 'valor_nuevo' => 'Reenviado', 'usuario_id' => $admin->id, 'usuario_nombre' => 'Admin Prueba',
        'descripcion' => 'El recordatorio de pago de la cuota 1 del crédito CREA-PRUEBA-001 se volvió a poner en la cola de envío.',
    ]);
});

test('reintentar un recordatorio que ya no aplica lo descarta sin enviar', function (Closure $cambio, string $motivo) {
    $cuota = cuotaConRecordatorioFallido();
    $cambio($cuota);
    $admin = administradorDeCorreos();
    $id = DB::table('failed_jobs')->value('id');
    Mail::fake();

    $this->actingAs($admin)->post(route('correos-fallidos.reintentar', $id))
        ->assertSessionHas('warning', "No se reenvió el recordatorio de pago de la cuota 1 del crédito CREA-PRUEBA-001: {$motivo}. Se quitó de la lista.");
    enviarCola();

    Mail::assertNothingSent();
    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and(DB::table('jobs')->count())->toBe(0)
        ->and($cuota->fresh()->recordatorio_enviado_at)->toBeNull();
    $this->assertDatabaseHas('auditoria_log', [
        'entidad' => 'CorreoFallido', 'entidad_id' => $id, 'accion' => 'deleted',
        'valor_anterior' => 'Fallido', 'valor_nuevo' => 'Descartado', 'usuario_id' => $admin->id,
        'descripcion' => "El recordatorio de pago de la cuota 1 del crédito CREA-PRUEBA-001 se descartó al reintentar: {$motivo}.",
    ]);
})->with([
    'cuota pagada'          => [fn (Amortizacion $c) => $c->update(['estado' => 'Pagado', 'pago_restante' => 0]), 'la cuota ya está pagada'],
    'acreditado sin correo' => [fn (Amortizacion $c) => $c->credito->acreditado->update(['correo' => null]), 'el acreditado ya no tiene correo'],
    'cuota ya vencida'      => [fn () => test()->travel(4)->days(), 'la cuota ya venció el 08/10/2026'],
]);

test('descartar quita el fallo sin enviar y deja registro de quién lo hizo', function () {
    $cuota = cuotaConRecordatorioFallido();
    $admin = administradorDeCorreos();
    $id = DB::table('failed_jobs')->value('id');
    Mail::fake();

    $this->actingAs($admin)->post(route('correos-fallidos.descartar', $id))
        ->assertSessionHas('success', 'Se descartó el correo. No se envió nada.');
    enviarCola();

    Mail::assertNothingSent();
    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and($cuota->fresh()->recordatorio_enviado_at)->toBeNull();
    $this->assertDatabaseHas('auditoria_log', [
        'entidad' => 'CorreoFallido', 'entidad_id' => $id, 'accion' => 'deleted',
        'valor_anterior' => 'Fallido', 'valor_nuevo' => 'Descartado', 'usuario_id' => $admin->id,
    ]);
    expect(DB::table('auditoria_log')->where('entidad', 'CorreoFallido')->value('descripcion'))
        ->toStartWith('El recordatorio de pago de la cuota 1 del crédito CREA-PRUEBA-001 se descartó sin reenviar.')
        ->not->toContain('ana.prueba@ejemplo.com');
});

test('un usuario sin rol Administrador recibe 403', function (string $metodo, string $ruta, bool $conId) {
    cuotaConRecordatorioFallido();
    $id = DB::table('failed_jobs')->value('id');
    $operativo = User::factory()->create(['tipo' => 'operativo']);

    $this->actingAs($operativo)->{$metodo}(route($ruta, $conId ? ['fallo' => $id] : []))->assertForbidden();

    expect(DB::table('failed_jobs')->count())->toBe(1)
        ->and(DB::table('jobs')->count())->toBe(0);
})->with([
    'ver la lista'     => ['get', 'correos-fallidos.index', false],
    'reintentar'       => ['post', 'correos-fallidos.reintentar', true],
    'reintentar todos' => ['post', 'correos-fallidos.reintentar-todos', false],
    'descartar'        => ['post', 'correos-fallidos.descartar', true],
]);

test('si la cuota ya se volvió a avisar, el reintento se descarta y no se manda doble', function () {
    cuotaConRecordatorioFallido();
    $admin = administradorDeCorreos();
    $id = DB::table('failed_jobs')->value('id');
    Mail::fake();

    // Antes de revisar los fallos, el administrador usa el botón de Recordatorios de pago
    $this->actingAs($admin)->post(route('recordatorios.enviar'), ['dias' => 3]);
    enviarCola();

    $this->actingAs($admin)->post(route('correos-fallidos.reintentar', $id))
        ->assertSessionHas('warning', fn ($mensaje) => str_contains($mensaje, ': ya se le volvió a enviar el aviso el 05/10/2026 12:0'));
    enviarCola();

    Mail::assertSent(RecordatorioPagoMail::class, 1);
});

test('dos clics en reintentar mandan un solo correo', function () {
    cuotaConRecordatorioFallido();
    $admin = administradorDeCorreos();
    $id = DB::table('failed_jobs')->value('id');
    Mail::fake();

    $this->actingAs($admin)->post(route('correos-fallidos.reintentar', $id))->assertSessionHas('success');
    $this->actingAs($admin)->post(route('correos-fallidos.reintentar', $id))
        ->assertSessionHas('warning', 'Ese correo ya no estaba en la lista: alguien más ya lo reintentó o lo descartó.');
    enviarCola();

    Mail::assertSent(RecordatorioPagoMail::class, 1);
    expect(DB::table('auditoria_log')->where('entidad', 'CorreoFallido')->count())->toBe(1);
});

test('reintentar todos reenvía los que aplican y descarta los demás con su motivo', function () {
    cuotaConRecordatorioFallido('CREA-PRUEBA-001', 'ana.prueba@ejemplo.com');
    cuotaConRecordatorioFallido('CREA-PRUEBA-002', 'beto.prueba@ejemplo.com')->update(['estado' => 'Pagado', 'pago_restante' => 0]);
    Mail::fake();

    $this->actingAs(administradorDeCorreos())->post(route('correos-fallidos.reintentar-todos'))
        ->assertSessionHas('success', 'Se volvieron a poner en la cola de envío 1 de 2 correo(s).')
        ->assertSessionHas('warning', 'No se reenvió el recordatorio de pago de la cuota 1 del crédito CREA-PRUEBA-002: la cuota ya está pagada. Se quitó de la lista.');
    enviarCola();

    Mail::assertSent(RecordatorioPagoMail::class, 1);
    Mail::assertSent(RecordatorioPagoMail::class, fn ($m) => $m->hasTo('ana.prueba@ejemplo.com'));
    expect(DB::table('failed_jobs')->count())->toBe(0);
});

test('la bitácora de tareas programadas muestra cuántos correos fallidos hay', function () {
    $admin = administradorDeCorreos();

    $this->withoutVite()->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page->where('correos_fallidos', 0));

    cuotaConRecordatorioFallido();

    $this->withoutVite()->actingAs($admin)->get(route('bitacora-tareas.index'))
        ->assertInertia(fn (Assert $page) => $page->where('correos_fallidos', 1));
});
