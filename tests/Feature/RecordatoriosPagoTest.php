<?php

use App\Mail\RecordatorioPagoMail;
use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

/**
 * Crea un acreditado con crédito y una cuota por cada desfase de días
 * respecto a hoy (Mérida). Las claves son el numero_cuota.
 *
 * @param  array<int, array{0:int, 1:string}>  $cuotas  numero => [desfaseDias, estado]
 */
function acreditadoConCuotasPorVencer(?string $correo, array $cuotas): void
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Modalidad-' . uniqid(),
        'tasa_interes'   => 0.00,
        'tasa_moratoria' => 0.00,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Acreditado ' . uniqid(),
        'municipio'       => 'Mérida',
        'correo'          => $correo,
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'REC-' . uniqid(),
        'monto_otorgado'         => 6000,
        'plazo_meses'            => count($cuotas),
        'fecha_entrega'          => Carbon::today('America/Merida')->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => 'Activo',
    ]);

    foreach ($cuotas as $numero => [$desfase, $estado]) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $numero,
            'fecha_vencimiento'          => Carbon::today('America/Merida')->addDays($desfase)->toDateString(),
            'saldo_insoluto'             => 6000,
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 0,
            'cuota_fija'                 => 1000,
            'pago_restante'              => $estado === 'Pagado' ? 0 : 1000,
            'estado'                     => $estado,
        ]);
    }
}

function usuarioAdministrador(): User
{
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');

    return $admin;
}

beforeEach(function () {
    Mail::fake();
    Role::create(['name' => 'Administrador']);

    // 02:00 UTC = 20:00 del día anterior en Mérida: la fecha UTC y la de Mérida difieren.
    $this->travelTo(Carbon::parse('2026-10-01 02:00', 'UTC'));
});

test('enviar dos veces seguidas manda un solo correo por cuota', function () {
    acreditadoConCuotasPorVencer('a@prueba.test', [1 => [1, 'Pendiente'], 2 => [3, 'Parcial']]);
    $admin = usuarioAdministrador();

    $this->actingAs($admin)->post(route('recordatorios.enviar'), ['dias' => 3])
        ->assertSessionHas('success', 'Recordatorios encolados: 2. Ya avisadas antes (no se reenvían): 0. Sin correo: 0.');

    $this->actingAs($admin)->post(route('recordatorios.enviar'), ['dias' => 3])
        ->assertSessionHas('success', 'Recordatorios encolados: 0. Ya avisadas antes (no se reenvían): 2. Sin correo: 0.');

    Mail::assertSent(RecordatorioPagoMail::class, 2);
    Mail::assertSent(RecordatorioPagoMail::class, fn ($m) => $m->cuota === 1 && $m->hasTo('a@prueba.test'));
    Mail::assertSent(RecordatorioPagoMail::class, fn ($m) => $m->cuota === 2 && $m->hasTo('a@prueba.test'));
});

test('el proceso diario y el botón no se duplican entre sí', function () {
    acreditadoConCuotasPorVencer('a@prueba.test', [1 => [3, 'Pendiente']]);

    $this->artisan('crea:recordatorios-pago')->assertSuccessful();
    $this->artisan('crea:recordatorios-pago')->assertSuccessful();
    $this->actingAs(usuarioAdministrador())->post(route('recordatorios.enviar'), ['dias' => 3]);

    Mail::assertSent(RecordatorioPagoMail::class, 1);
});

test('una cuota que ya está pagada no recibe aviso', function () {
    acreditadoConCuotasPorVencer('a@prueba.test', [1 => [2, 'Pagado'], 2 => [3, 'Pendiente']]);

    $this->actingAs(usuarioAdministrador())->post(route('recordatorios.enviar'), ['dias' => 3]);

    Mail::assertSent(RecordatorioPagoMail::class, 1);
    Mail::assertNotSent(RecordatorioPagoMail::class, fn ($m) => $m->cuota === 1);
    expect(DB::table('amortizaciones')->where('numero_cuota', 1)->value('recordatorio_enviado_at'))->toBeNull();
});

test('un acreditado sin correo se cuenta como sin correo y no truena', function () {
    acreditadoConCuotasPorVencer(null, [1 => [3, 'Pendiente']]);

    $this->actingAs(usuarioAdministrador())->post(route('recordatorios.enviar'), ['dias' => 3])
        ->assertRedirect()
        ->assertSessionHas('success', 'Recordatorios encolados: 0. Ya avisadas antes (no se reenvían): 0. Sin correo: 1.');

    $this->artisan('crea:recordatorios-pago')->assertSuccessful();

    Mail::assertNothingSent();
    expect(DB::table('amortizaciones')->whereNotNull('recordatorio_enviado_at')->count())->toBe(0);
});

test('un usuario que no es Administrador no puede disparar el envío', function () {
    acreditadoConCuotasPorVencer('a@prueba.test', [1 => [2, 'Pendiente']]);
    $operativo = User::factory()->create(['tipo' => 'operativo']);

    $this->actingAs($operativo)->post(route('recordatorios.enviar'), ['dias' => 3])->assertForbidden();

    Mail::assertNothingSent();
    expect(DB::table('amortizaciones')->whereNotNull('recordatorio_enviado_at')->count())->toBe(0);
});

test('el botón no bloquea el aviso de 3 días antes', function () {
    acreditadoConCuotasPorVencer('a@prueba.test', [1 => [10, 'Pendiente']]);
    $admin = usuarioAdministrador();

    // Una ventana más larga que la del aviso diario se rechaza
    $this->actingAs($admin)->post(route('recordatorios.enviar'), ['dias' => 30])->assertSessionHasErrors('dias');
    // Con la ventana permitida, una cuota a 10 días todavía no se avisa ni se marca
    $this->actingAs($admin)->post(route('recordatorios.enviar'), ['dias' => 3]);
    Mail::assertNothingSent();

    // 7 días después la cuota vence en 3 días y el proceso diario sí la avisa
    $this->travel(7)->days();
    $this->artisan('crea:recordatorios-pago')->assertSuccessful();

    Mail::assertSent(RecordatorioPagoMail::class, 1);
});
