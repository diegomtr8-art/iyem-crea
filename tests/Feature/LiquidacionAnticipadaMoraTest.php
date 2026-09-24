<?php

use App\Models\Amortizacion;
use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Ticket fix/crea-columnas-mora (4.5).
 *
 * La liquidación anticipada debe sumar TODA la mora generada, sin importar su
 * origen: el cron (crea:update-moratorio) o la aplicación de un pago. Antes del
 * fix, los pagos escribían solo `interes_moratorio_generado` mientras la
 * liquidación leía `moratorio_acumulado`, subcotizando el monto al ciudadano.
 */
function crearCreditoMoraLiquidacion(): Credito
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Mora-Liq-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Mora Liquidacion',
        'municipio'       => 'Mérida',
        'sexo'            => 'H',
        'rfc'             => 'MORLQ001',
        'curp'            => 'MORLQ001HDFXYZ00',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'MORA-LIQ-' . uniqid(),
        'monto_otorgado'         => 2000,
        'plazo_meses'            => 2,
        'fecha_entrega'          => '2026-05-01',
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus'                => 'Activo',
    ]);

    // Cuota 1 vence 2026-06-05: aún NO vencida cuando corre el cron (2026-05-26),
    // por lo que su mora nacerá de un pago.
    // Cuota 2 vence 2026-05-16: vencida cuando corre el cron, su mora nacerá de ahí.
    foreach ([1 => '2026-06-05', 2 => '2026-05-16'] as $numero => $vencimiento) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $numero,
            'fecha_vencimiento'          => $vencimiento,
            'saldo_insoluto'             => 1000,
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 0,
            'cuota_fija'                 => 1000,
            'pago_restante'              => 1000,
            'estado'                     => 'Pendiente',
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado'        => 0,
        ]);
    }

    return $credito;
}

afterEach(function () {
    Carbon::setTestNow();
});

test('la liquidacion anticipada incluye toda la mora generada', function () {
    // 1) Mora por cron: solo la cuota 2 está vencida a esta fecha.
    Carbon::setTestNow(Carbon::create(2026, 5, 26, 12, 0, 0, 'America/Merida'));
    $credito = crearCreditoMoraLiquidacion();
    Artisan::call('crea:update-moratorio');

    $cuotaCron = Amortizacion::where('credito_id', $credito->id)->where('numero_cuota', 2)->first();
    expect((float) $cuotaCron->interes_moratorio_generado)->toBe(4.86);

    // 2) Mora por pago: la cuota 1 ya vence (10 días) y queda Parcial.
    Carbon::setTestNow(Carbon::create(2026, 6, 15, 12, 0, 0, 'America/Merida'));
    $operativo = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);

    $this->actingAs($operativo)
        ->post(route('pagos.store', $credito->id), [
            'monto_recibido' => 500,
            'fecha_pago'     => '2026-06-15',
            'forma_pago'     => 'Efectivo',
        ])->assertRedirect();

    $cuotaPago = Amortizacion::where('credito_id', $credito->id)->where('numero_cuota', 1)->first();
    expect($cuotaPago->estado)->toBe('Parcial');
    expect((float) $cuotaPago->interes_moratorio_generado)->toBe(4.86);

    // 3) El espejo queda sincronizado en ambas columnas.
    foreach ([$cuotaPago, $cuotaCron->fresh()] as $cuota) {
        expect((float) $cuota->moratorio_acumulado)->toBe((float) $cuota->interes_moratorio_generado);
    }

    // 4) Liquidación anticipada como ciudadano vinculado al crédito.
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);
    SolicitudCredito::create([
        'user_id'    => $ciudadano->id,
        'credito_id' => $credito->id,
    ]);

    $response = $this->actingAs($ciudadano)->getJson(route('portal.credito.liquidacion'));
    $response->assertOk();

    // 4.86 (mora nacida del pago, cuota 1) + 4.86 (mora nacida del cron, cuota 2).
    expect((float) $response->json('mora_acumulada'))->toBe(9.72);
});
