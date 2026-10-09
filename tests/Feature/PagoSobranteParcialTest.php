<?php

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;

/**
 * Fase 2 — Ticket 4.3
 * Sobrante menor a 1 cuota → se aplica automáticamente a la siguiente cuota
 * como pago parcial (pago adelantado), sin recalcular la tabla ni mostrar elección.
 */
test('sobrante menor a una cuota se aplica como parcial a la siguiente cuota sin recalcular', function () {
    $user = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);
    $this->actingAs($user);

    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Artesanal-' . uniqid(),
        'tasa_interes'   => 0.00,
        'tasa_moratoria' => 0.00,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Sobrante Parcial',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TKT43-' . uniqid(),
        'monto_otorgado'         => 6000,
        'plazo_meses'            => 3,
        'fecha_entrega'          => Carbon::today()->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => 'Activo',
    ]);

    $saldo = 6000;
    foreach ([2000, 2000, 2000] as $i => $monto) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $i + 1,
            'fecha_vencimiento'          => Carbon::today()->addMonths($i)->toDateString(),
            'saldo_insoluto'             => $saldo,
            'capital_esperado'           => $monto,
            'interes_ordinario_esperado' => 0,
            'cuota_fija'                 => $monto,
            'pago_restante'              => $monto,
            'estado'                     => 'Pendiente',
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado'        => 0,
        ]);
        $saldo -= $monto;
    }

    $response = $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 2300,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
        'observaciones'  => 'Test sobrante parcial menor a una cuota',
    ]);

    $response->assertRedirect();

    $cuota = fn (int $n) => Amortizacion::where('credito_id', $credito->id)
        ->where('numero_cuota', $n)
        ->first();

    // Cuota 1: pagada completa
    expect($cuota(1)->estado)->toBe('Pagado');
    expect((float) $cuota(1)->pago_restante)->toBe(0.0);
    expect((float) $cuota(1)->capital_pagado)->toBe(2000.0);

    // Cuota 2: parcial con el sobrante, sin recálculo de la tabla
    expect($cuota(2)->estado)->toBe('Parcial');
    expect((float) $cuota(2)->pago_restante)->toBe(1700.0);
    expect((float) $cuota(2)->capital_pagado)->toBe(300.0);
    expect((float) $cuota(2)->cuota_fija)->toBe(2000.0);
    expect((float) $cuota(2)->capital_esperado)->toBe(2000.0);

    // Cuota 3: intacta
    expect($cuota(3)->estado)->toBe('Pendiente');
    expect((float) $cuota(3)->pago_restante)->toBe(2000.0);
    expect((float) $cuota(3)->capital_pagado)->toBe(0.0);

    // Registro del pago: todo aplicado, con snapshot
    $pago = Pago::where('credito_id', $credito->id)->first();
    expect($pago)->not->toBeNull();
    expect((float) $pago->monto_recibido)->toBe(2300.0);
    expect((float) $pago->aplicado_mora)->toBe(0.0);
    expect((float) $pago->aplicado_ordinario)->toBe(0.0);
    expect((float) $pago->aplicado_capital)->toBe(2300.0);
    expect(count($pago->snapshot_amortizaciones ?? []))->toBe(3);
    expect(array_column($pago->cuotas_cubiertas ?? [], 'cuota'))->toContain(1, 2);

    // El crédito sigue activo
    expect($credito->fresh()->estatus)->toBe('Activo');
});
