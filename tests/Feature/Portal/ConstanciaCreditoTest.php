<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;

/**
 * La constancia solo se emite a quien tiene crédito Activo y sin cuotas vencidas.
 */
function ciudadanoConCredito(string $estatus, string $vencimiento): User
{
    $user = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Emprendedores-' . uniqid(),
        'tasa_interes'   => 0.00,
        'tasa_moratoria' => 0.00,
    ]);

    $acreditado = Acreditado::create(['nombre_completo' => 'Acreditado Prueba', 'municipio' => 'Mérida']);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'CT-' . uniqid(),
        'monto_otorgado'         => 3000,
        'plazo_meses'            => 3,
        'fecha_entrega'          => Carbon::today()->subMonths(2)->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => $estatus,
    ]);

    $credito->amortizaciones()->create([
        'numero_cuota'               => 1,
        'fecha_vencimiento'          => $vencimiento,
        'saldo_insoluto'             => 3000,
        'capital_esperado'           => 1000,
        'interes_ordinario_esperado' => 0,
        'cuota_fija'                 => 1000,
        'pago_restante'              => $estatus === 'Liquidado' ? 0 : 1000,
        'estado'                     => $estatus === 'Liquidado' ? 'Pagado' : 'Pendiente',
    ]);

    SolicitudCredito::create([
        'user_id'         => $user->id,
        'nombre_completo' => 'Ciudadano Prueba',
        'estatus'         => 'Aprobada',
        'acreditado_id'   => $acreditado->id,
        'credito_id'      => $credito->id,
    ]);

    return $user;
}

test('un crédito activo y al corriente recibe la constancia', function () {
    $user = ciudadanoConCredito('Activo', Carbon::today()->addMonth()->toDateString());

    $this->actingAs($user)->get(route('portal.credito.constancia'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('un crédito con cuotas vencidas no recibe la constancia', function () {
    $user = ciudadanoConCredito('Activo', Carbon::today()->subDays(10)->toDateString());

    $this->actingAs($user)->get(route('portal.credito.constancia'))
        ->assertRedirect(route('portal.credito'))
        ->assertSessionHas('warning', 'Tu crédito no está al corriente o ya se liquidó. Acércate al IYEM para solicitar tu constancia.');
});

test('un crédito liquidado no recibe la constancia', function () {
    $user = ciudadanoConCredito('Liquidado', Carbon::today()->subMonth()->toDateString());

    $this->actingAs($user)->get(route('portal.credito.constancia'))
        ->assertRedirect(route('portal.credito'))
        ->assertSessionHas('warning');
});
