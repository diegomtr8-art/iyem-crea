<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;

/**
 * P-06 / P-11: un ciudadano sin crédito que entra a "Mi crédito" o pide el
 * estado de cuenta debe volver a Inicio, no ver un 500 ni un 404.
 */
function ciudadanoMiCredito(bool $conSolicitud, bool $conCredito): User
{
    $user = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    if (!$conSolicitud) {
        return $user;
    }

    $datos = ['user_id' => $user->id, 'nombre_completo' => 'Ciudadano Prueba', 'estatus' => 'Aprobada'];

    if ($conCredito) {
        $modalidad = ModalidadCrea::create([
            'nombre'         => 'Emprendedores-' . uniqid(),
            'tasa_interes'   => 0.00,
            'tasa_moratoria' => 0.00,
        ]);

        $acreditado = Acreditado::create([
            'nombre_completo' => 'Acreditado Prueba',
            'municipio'       => 'Mérida',
        ]);

        $credito = $acreditado->creditos()->create([
            'modalidad_id'           => $modalidad->id,
            'clave_contrato'         => 'MC-' . uniqid(),
            'monto_otorgado'         => 3000,
            'plazo_meses'            => 3,
            'fecha_entrega'          => Carbon::today()->toDateString(),
            'tasa_interes_ordinario' => 0,
            'tasa_interes_moratorio' => 0,
            'estatus'                => 'Activo',
        ]);

        $credito->amortizaciones()->create([
            'numero_cuota'               => 1,
            'fecha_vencimiento'          => Carbon::today()->addMonth()->toDateString(),
            'saldo_insoluto'             => 3000,
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 0,
            'cuota_fija'                 => 1000,
            'pago_restante'              => 1000,
            'estado'                     => 'Pendiente',
        ]);

        $datos['acreditado_id'] = $acreditado->id;
        $datos['credito_id']    = $credito->id;
    }

    SolicitudCredito::create($datos);

    return $user;
}

test('sin solicitud, Mi crédito redirige a Inicio', function () {
    $user = ciudadanoMiCredito(conSolicitud: false, conCredito: false);

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertRedirect(route('portal.dashboard'));
});

test('con solicitud pero sin crédito, Mi crédito redirige a Inicio', function () {
    $user = ciudadanoMiCredito(conSolicitud: true, conCredito: false);

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertRedirect(route('portal.dashboard'));
});

test('sin crédito, el estado de cuenta redirige a Inicio con un aviso', function () {
    $user = ciudadanoMiCredito(conSolicitud: true, conCredito: false);

    $this->actingAs($user)
        ->get(route('portal.credito.estado-cuenta.pdf'))
        ->assertRedirect(route('portal.dashboard'))
        ->assertSessionHas('info', 'Aún no tienes un crédito registrado.');
});

test('con crédito, Mi crédito y el estado de cuenta funcionan igual que antes', function () {
    $user = ciudadanoMiCredito(conSolicitud: true, conCredito: true);

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/MiCredito'));

    $this->actingAs($user)
        ->get(route('portal.credito.estado-cuenta.pdf'))
        ->assertOk();
});
