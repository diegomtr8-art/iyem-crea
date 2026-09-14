<?php

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;

/**
 * Fixture del documento del Ticket 4.3: $44,670.20 a 24 meses, 7% anual
 * (i = 0.00583333), cuota fija $2,000. La cuota 1 = capital $1,739.42 +
 * interés $260.58; el saldo de capital tras la cuota 1 es $42,930.78.
 */
function crearCreditoSobrepago(array $overrides = [])
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Emprendedores-Test-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Sobrepago Aceptacion',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create(array_merge([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TKT43B-' . uniqid(),
        'monto_otorgado'         => 44670.20,
        'plazo_meses'            => 24,
        'fecha_entrega'          => Carbon::today()->subMonths(1)->toDateString(),
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus'                => 'Activo',
    ], $overrides));

    $i = 0.00583333; // 7 / 100 / 12

    // Cuota 1: capital 1,739.42 + interés 260.58 (valores exactos del doc).
    $credito->amortizaciones()->create([
        'numero_cuota'               => 1,
        'fecha_vencimiento'          => Carbon::today()->toDateString(),
        'saldo_insoluto'             => 44670.20,
        'capital_esperado'           => 1739.42,
        'interes_ordinario_esperado' => 260.58,
        'cuota_fija'                 => 2000.00,
        'pago_restante'              => 2000.00,
        'estado'                     => 'Pendiente',
        'capital_pagado'             => 0,
        'interes_ordinario_pagado'   => 0,
        'interes_moratorio_pagado'   => 0,
        'interes_moratorio_generado' => 0,
        'moratorio_acumulado'        => 0,
    ]);

    // Cuotas 2..24: cuota fija $2,000 con split francés; la suma de capital
    // de cuotas 2..24 es exactamente $42,930.78 (la cuota 24 absorbe el residuo).
    $principal = 44670.20 - 1739.42; // 42,930.78
    for ($n = 2; $n <= 24; $n++) {
        if ($n < 24) {
            $interes = round($principal * $i, 2);
            $capital = 2000 - $interes;
        } else {
            $capital = round($principal, 2);
            $interes = round(2000 - $capital, 2);
        }

        $credito->amortizaciones()->create([
            'numero_cuota'               => $n,
            'fecha_vencimiento'          => Carbon::today()->addMonths($n - 1)->toDateString(),
            'saldo_insoluto'             => round($principal, 2),
            'capital_esperado'           => round($capital, 2),
            'interes_ordinario_esperado' => round($interes, 2),
            'cuota_fija'                 => 2000.00,
            'pago_restante'              => 2000.00,
            'estado'                     => 'Pendiente',
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado'        => 0,
        ]);

        $principal -= $capital;
    }

    return $credito;
}

function operativoVerificado(): User
{
    return User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);
}

function cuotaDe(Credito $credito, int $numero): Amortizacion
{
    return Amortizacion::where('credito_id', $credito->id)
        ->where('numero_cuota', $numero)
        ->first();
}

/**
 * Caso 2 — Paga $5,000 con Pago adelantado: cuota 2 completa + $1,000 en cuota 3.
 */
test('caso 2: sobrante de 3,000 con pago adelantado cubre cuota 2 y parcial de cuota 3', function () {
    $this->actingAs(operativoVerificado());
    $credito = crearCreditoSobrepago();

    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 5000,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
        'tipo_abono'     => 'Adelantado',
    ])->assertRedirect();

    expect(cuotaDe($credito, 1)->estado)->toBe('Pagado');
    expect(cuotaDe($credito, 2)->estado)->toBe('Pagado');
    expect((float) cuotaDe($credito, 2)->pago_restante)->toBe(0.0);

    $cuota3 = cuotaDe($credito, 3);
    expect($cuota3->estado)->toBe('Parcial');
    expect((float) $cuota3->pago_restante)->toBe(1000.0);

    // Plazo y cuota intactos (sin recálculo)
    expect((float) $cuota3->cuota_fija)->toBe(2000.0);
    expect((float) cuotaDe($credito, 4)->cuota_fija)->toBe(2000.0);

    $pago = Pago::where('credito_id', $credito->id)->first();
    expect($pago->tipo_abono)->toBe('Adelantado');
    expect($pago->sobrante_aplicado)->toBeNull();
});

/**
 * Caso 3 — Paga $5,000 con Reducir cuota: 23 cuotas en $1,860.24 y Σ capital = S'.
 */
test('caso 3: sobrante de 3,000 con reducir cuota deja 23 cuotas de 1,860.24', function () {
    $this->actingAs(operativoVerificado());
    $credito = crearCreditoSobrepago();

    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 5000,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
        'tipo_abono'     => 'Reducir Cuota',
    ])->assertRedirect();

    expect(cuotaDe($credito, 1)->estado)->toBe('Pagado');

    $sumaCapital = 0.0;
    for ($n = 2; $n <= 24; $n++) {
        $cuota = cuotaDe($credito, $n);
        expect((float) $cuota->cuota_fija)->toBe(1860.24);
        $sumaCapital += (float) $cuota->capital_esperado;
    }

    expect(round($sumaCapital, 2))->toBe(39930.78);

    $pago = Pago::where('credito_id', $credito->id)->first();
    expect($pago->tipo_abono)->toBe('Reducir Cuota');
    expect((float) $pago->sobrante_aplicado)->toBe(3000.0);
});

/**
 * Caso 4 — Paga $5,000 con Reducir plazo: 21 cuotas de $2,000 + final $578.88.
 */
test('caso 4: sobrante de 3,000 con reducir plazo deja 21 cuotas de 2,000 y final de 578.88', function () {
    $this->actingAs(operativoVerificado());
    $credito = crearCreditoSobrepago();

    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 5000,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
        'tipo_abono'     => 'Reducir Plazo',
    ])->assertRedirect();

    expect(cuotaDe($credito, 1)->estado)->toBe('Pagado');

    // 21 cuotas completas (cuotas 2..22)
    for ($n = 2; $n <= 22; $n++) {
        expect((float) cuotaDe($credito, $n)->cuota_fija)->toBe(2000.0);
    }

    // Cuota 23 final
    expect((float) cuotaDe($credito, 23)->cuota_fija)->toBe(578.88);

    // Cuota 24 eliminada
    $cuota24 = cuotaDe($credito, 24);
    expect($cuota24->estado)->toBe('Pagado');
    expect((float) $cuota24->cuota_fija)->toBe(0.0);
    expect((float) $cuota24->capital_esperado)->toBe(0.0);
    expect((float) $cuota24->pago_restante)->toBe(0.0);
    expect($cuota24->observaciones)->toContain('eliminada por reducción de plazo');

    $sumaCapital = 0.0;
    for ($n = 2; $n <= 24; $n++) {
        $sumaCapital += (float) cuotaDe($credito, $n)->capital_esperado;
    }
    expect(round($sumaCapital, 2))->toBe(39930.78);

    $pago = Pago::where('credito_id', $credito->id)->first();
    expect($pago->tipo_abono)->toBe('Reducir Plazo');
    expect((float) $pago->sobrante_aplicado)->toBe(3000.0);
});

/**
 * Caso 6 — Cancelar un pago que usó Reducir cuota restaura la tabla exacta (snapshot).
 */
test('caso 6: cancelar pago con reducir cuota restaura la tabla a sus valores originales', function () {
    $this->actingAs(operativoVerificado());
    $credito = crearCreditoSobrepago();

    $estadoPrevio = [];
    foreach ($credito->amortizaciones as $row) {
        $estadoPrevio[$row->numero_cuota] = [
            'capital_esperado'           => (float) $row->capital_esperado,
            'interes_ordinario_esperado' => (float) $row->interes_ordinario_esperado,
            'cuota_fija'                 => (float) $row->cuota_fija,
            'saldo_insoluto'             => (float) $row->saldo_insoluto,
            'pago_restante'              => (float) $row->pago_restante,
            'capital_pagado'             => (float) $row->capital_pagado,
            'interes_ordinario_pagado'   => (float) $row->interes_ordinario_pagado,
            'estado'                     => $row->estado,
        ];
    }

    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 5000,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
        'tipo_abono'     => 'Reducir Cuota',
    ])->assertRedirect();

    $pago = Pago::where('credito_id', $credito->id)->first();
    expect($pago)->not->toBeNull();

    $this->post(route('pagos.cancelar', $pago->id), [
        'motivo_cancelacion' => 'Test caso 6: reversión de Reducir cuota',
    ])->assertRedirect();

    expect($pago->fresh()->cancelado)->toBeTrue();

    foreach ($estadoPrevio as $numero => $previo) {
        $row = cuotaDe($credito, $numero);
        expect((float) $row->capital_esperado)->toBe($previo['capital_esperado']);
        expect((float) $row->interes_ordinario_esperado)->toBe($previo['interes_ordinario_esperado']);
        expect((float) $row->cuota_fija)->toBe($previo['cuota_fija']);
        expect((float) $row->saldo_insoluto)->toBe($previo['saldo_insoluto']);
        expect((float) $row->pago_restante)->toBe($previo['pago_restante']);
        expect((float) $row->capital_pagado)->toBe($previo['capital_pagado']);
        expect((float) $row->interes_ordinario_pagado)->toBe($previo['interes_ordinario_pagado']);
        expect($row->estado)->toBe($previo['estado']);
    }
});

/**
 * Caso 7 — Sobrante > capital: pago que excede el monto de liquidación se rechaza.
 */
test('caso 7: sobrepago mayor al capital se rechaza y no registra pago', function () {
    $this->actingAs(operativoVerificado());

    $modalidad = ModalidadCrea::create(['nombre' => 'Art-Test-' . uniqid(), 'tasa_interes' => 0, 'tasa_moratoria' => 0]);
    $acreditado = Acreditado::create(['nombre_completo' => 'Sobrepago Exceso', 'municipio' => 'Mérida']);
    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TKT43C-' . uniqid(),
        'monto_otorgado'         => 500,
        'plazo_meses'            => 1,
        'fecha_entrega'          => Carbon::today()->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => 'Activo',
    ]);
    $credito->amortizaciones()->create([
        'numero_cuota'               => 1,
        'fecha_vencimiento'          => Carbon::today()->addMonth()->toDateString(),
        'saldo_insoluto'             => 500,
        'capital_esperado'           => 500,
        'interes_ordinario_esperado' => 0,
        'cuota_fija'                 => 500,
        'pago_restante'              => 500,
        'estado'                     => 'Pendiente',
        'capital_pagado'             => 0,
        'interes_ordinario_pagado'   => 0,
        'interes_moratorio_pagado'   => 0,
        'interes_moratorio_generado' => 0,
        'moratorio_acumulado'        => 0,
    ]);

    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 800,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
    ])->assertSessionHasErrors('monto_recibido');

    expect(Pago::where('credito_id', $credito->id)->count())->toBe(0);
});

/**
 * Gate 2 — Con mora no se permite abonar: se rechaza hasta ponerse al día.
 */
test('gate 2: con mora se bloquea el pago que excede ponerse al día', function () {
    $this->actingAs(operativoVerificado());

    $modalidad = ModalidadCrea::create(['nombre' => 'Emp-Test-' . uniqid(), 'tasa_interes' => 7, 'tasa_moratoria' => 17.5]);
    $acreditado = Acreditado::create(['nombre_completo' => 'Sobrepago Mora', 'municipio' => 'Mérida']);
    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TKT43D-' . uniqid(),
        'monto_otorgado'         => 2000,
        'plazo_meses'            => 2,
        'fecha_entrega'          => Carbon::today()->subDays(45)->toDateString(),
        'tasa_interes_ordinario' => 7,
        'tasa_interes_moratorio' => 17.5,
        'estatus'                => 'Activo',
    ]);

    // Cuota 1 vencida hace 10 días (genera mora) y cuota 2 futura.
    foreach ([1, 2] as $n) {
        $vencimiento = $n === 1
            ? Carbon::today()->subDays(10)->toDateString()
            : Carbon::today()->addMonth()->toDateString();

        $credito->amortizaciones()->create([
            'numero_cuota'               => $n,
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

    // $1,200 > costo de ponerse al día (~cuota vencida + mora) y < máximo de liquidación.
    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 1200,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
    ])->assertSessionHasErrors('monto_recibido');

    expect(Pago::where('credito_id', $credito->id)->count())->toBe(0);
});
