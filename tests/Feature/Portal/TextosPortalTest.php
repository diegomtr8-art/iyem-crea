<?php

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;

/**
 * Textos del portal ciudadano: C-01 (estatus del crédito) y C-05 (fecha de pago).
 */
function ciudadanoConCredito(string $estatus): array
{
    $user = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Emprendedores-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 0.00,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Ciudadano Prueba',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TXT-' . uniqid(),
        'monto_otorgado'         => 6000,
        'plazo_meses'            => 3,
        'fecha_entrega'          => Carbon::today()->toDateString(),
        'tasa_interes_ordinario' => 7,
        'tasa_interes_moratorio' => 0,
        'estatus'                => $estatus,
    ]);

    SolicitudCredito::create([
        'user_id'         => $user->id,
        'nombre_completo' => 'Ciudadano Prueba',
        'estatus'         => 'Aprobada',
        'acreditado_id'   => $acreditado->id,
        'credito_id'      => $credito->id,
    ]);

    return [$user, $credito, $acreditado];
}

test('C-01: un crédito Liquidado llega al dashboard como Liquidado, no como Activo', function () {
    [$user] = ciudadanoConCredito('Liquidado');

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/Dashboard')
            ->where('credito_activo.estatus', 'Liquidado'));
});

test('P-04: sin crédito registrado el dashboard manda credito_activo en null', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);
    SolicitudCredito::create([
        'user_id'         => $user->id,
        'nombre_completo' => 'Sin Credito',
        'estatus'         => 'Aprobada',
    ]);

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('solicitud.estatus', 'Aprobada')
            ->where('credito_activo', null));
});

test('C-05: la fecha de pago llega como dd/mm/aaaa', function () {
    [$user, $credito, $acreditado] = ciudadanoConCredito('Activo');

    Pago::create([
        'credito_id'     => $credito->id,
        'acreditado_id'  => $acreditado->id,
        'folio'          => 'REC-TXT-' . uniqid(),
        'monto_recibido' => 1000,
        'forma_pago'     => 'Efectivo',
        'fecha_pago'     => '2026-03-05',
        'registrado_por' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('credito.pagos.0.fecha_pago', '05/03/2026'));
});
