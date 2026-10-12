<?php

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\CondonacionFormal;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\User;
use Carbon\Carbon;

function crearCreditoCondonacion(): Credito
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Test-' . uniqid(),
        'tasa_interes'   => 0,
        'tasa_moratoria' => 0,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Condonacion',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TKC-' . uniqid(),
        'monto_otorgado'         => 3000,
        'plazo_meses'            => 3,
        'fecha_entrega'          => Carbon::today()->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => 'Activo',
    ]);

    foreach ([1, 2, 3] as $i) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $i,
            'fecha_vencimiento'          => Carbon::today()->addMonths($i)->toDateString(),
            'saldo_insoluto'             => 3000 - (($i - 1) * 1000),
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 200,
            'cuota_fija'                 => 1200,
            'pago_restante'              => 1200,
            'estado'                     => 'Pendiente',
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 100,
            'moratorio_acumulado'        => 100,
        ]);
    }

    return $credito;
}

function postCondonacion(Credito $credito, array $datos)
{
    $user = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);

    return test()->actingAs($user)->post(
        route('creditos.condonacion-formal.store', $credito->id),
        array_merge([
            'motivo'             => 'Prueba de condonación parcial',
            'fecha_autorizacion' => Carbon::today()->toDateString(),
        ], $datos)
    );
}

test('la condonacion parcial de capital reduce el capital esperado y el saldo', function () {
    $credito = crearCreditoCondonacion();

    postCondonacion($credito, [
        'tipo'                    => 'Parcial_Capital',
        'monto_condonado_capital' => 500,
    ])->assertRedirect();

    $cuota = fn (int $n) => Amortizacion::where('credito_id', $credito->id)
        ->where('numero_cuota', $n)
        ->first();

    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('capital_esperado'))->toBe(2500.0);
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('pago_restante'))->toBe(3100.0);

    // El saldo insoluto baja en la cuota condonada y en las siguientes
    expect((float) $cuota(1)->saldo_insoluto)->toBe(2500.0);
    expect((float) $cuota(2)->saldo_insoluto)->toBe(1500.0);
    expect((float) $cuota(3)->saldo_insoluto)->toBe(500.0);

    expect(CondonacionFormal::where('credito_id', $credito->id)->count())->toBe(1);
});

test('la condonacion parcial de intereses reduce el interes esperado y el saldo', function () {
    $credito = crearCreditoCondonacion();

    postCondonacion($credito, [
        'tipo'                      => 'Parcial_Intereses',
        'monto_condonado_intereses' => 500,
    ])->assertRedirect();

    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('interes_ordinario_esperado'))->toBe(100.0);
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('pago_restante'))->toBe(3100.0);

    // El capital proyectado y el saldo insoluto no se tocan en intereses
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('capital_esperado'))->toBe(3000.0);
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('saldo_insoluto'))->toBe(6000.0);

    expect(CondonacionFormal::where('credito_id', $credito->id)->count())->toBe(1);
});

test('la condonacion parcial de mora se rechaza sin registrar nada', function () {
    $credito = crearCreditoCondonacion();

    postCondonacion($credito, [
        'tipo'                 => 'Parcial_Mora',
        'monto_condonado_mora' => 150,
    ])->assertSessionHasErrors('tipo');

    expect(CondonacionFormal::where('credito_id', $credito->id)->count())->toBe(0);

    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('capital_esperado'))->toBe(3000.0);
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('interes_ordinario_esperado'))->toBe(600.0);
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('pago_restante'))->toBe(3600.0);
    expect((float) Amortizacion::where('credito_id', $credito->id)->sum('moratorio_acumulado'))->toBe(300.0);
});

test('no se puede condonar mas de lo pendiente', function () {
    $credito = crearCreditoCondonacion();

    postCondonacion($credito, [
        'tipo'                      => 'Parcial_Intereses',
        'monto_condonado_intereses' => 9999,
    ])->assertSessionHasErrors('monto_condonado_intereses');

    $suma = (float) Amortizacion::where('credito_id', $credito->id)->sum('interes_ordinario_esperado');

    expect($suma)->toBe(600.0);
    expect(CondonacionFormal::where('credito_id', $credito->id)->count())->toBe(0);
});
