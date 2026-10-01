<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\Reestructuracion;
use App\Models\User;

/**
 * Ticket 4.6 — Reestructuración.
 * T1: base = capital pendiente + interés ordinario devengado (el futuro no se capitaliza).
 *     Caso real CREA-2026-101 → diferencia $1,029.31.
 * T2: se rechaza cuando el monto resultante es <= 0.
 */

function baseReestructuraTest(): array
{
    $user = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);

    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Emprendedores-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Reestructuración',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'TKT46-' . uniqid(),
        'monto_otorgado'         => 30000,
        'plazo_meses'            => 24,
        'fecha_entrega'          => '2026-01-15',
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus'                => 'Activo',
    ]);

    return [$user, $credito];
}

test('T1: reestructura capital + interés devengado sin capitalizar el interés futuro', function () {
    [$user, $credito] = baseReestructuraTest();
    $this->actingAs($user);

    // Fixture exacto del documento (cuota, fecha, saldo_insoluto, capital, interés)
    $cuotas = [
        [1,  '2026-02-15', 30000.00, 1168.18, 175.00],
        [2,  '2026-03-15', 28831.82, 1174.99, 168.19],
        [3,  '2026-04-15', 27656.83, 1181.85, 161.33],
        [4,  '2026-05-15', 26474.98, 1188.74, 154.44],
        [5,  '2026-06-15', 25286.24, 1195.68, 147.50],
        [6,  '2026-07-15', 24090.56, 1202.65, 140.53],
        [7,  '2026-08-15', 22887.91, 1209.67, 133.51],
        [8,  '2026-09-15', 21678.24, 1216.72, 126.46],
        [9,  '2026-10-15', 20461.52, 1223.82, 119.36],
        [10, '2026-11-15', 19237.70, 1230.96, 112.22],
        [11, '2026-12-15', 18006.74, 1238.14, 105.04],
        [12, '2027-01-15', 16768.60, 1245.36, 97.82],
        [13, '2027-02-15', 15523.24, 1252.63, 90.55],
        [14, '2027-03-15', 14270.61, 1259.93, 83.25],
        [15, '2027-04-15', 13010.68, 1267.28, 75.90],
        [16, '2027-05-15', 11743.40, 1274.68, 68.50],
        [17, '2027-06-15', 10468.72, 1282.11, 61.07],
        [18, '2027-07-15', 9186.61,  1289.59, 53.59],
        [19, '2027-08-15', 7897.02,  1297.11, 46.07],
        [20, '2027-09-15', 6599.91,  1304.68, 38.50],
        [21, '2027-10-15', 5295.23,  1312.29, 30.89],
        [22, '2027-11-15', 3982.94,  1319.95, 23.23],
        [23, '2027-12-15', 2662.99,  1327.65, 15.53],
        [24, '2028-01-15', 1335.34,  1335.34, 7.79],
    ];

    foreach ($cuotas as [$numero, $fecha, $saldo, $capital, $interes]) {
        $pagada = $numero <= 3;
        $credito->amortizaciones()->create([
            'numero_cuota'               => $numero,
            'fecha_vencimiento'          => $fecha,
            'saldo_insoluto'             => $saldo,
            'capital_esperado'           => $capital,
            'interes_ordinario_esperado' => $interes,
            'cuota_fija'                 => round($capital + $interes, 2),
            'pago_restante'              => $pagada ? 0 : round($capital + $interes, 2),
            'estado'                     => $pagada ? 'Pagado' : 'Pendiente',
            'capital_pagado'             => $pagada ? $capital : 0,
            'interes_ordinario_pagado'   => $pagada ? $interes : 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado'        => 0,
        ]);
    }

    $sumaVieja = round($credito->amortizaciones()
        ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
        ->sum('pago_restante'), 2);

    $this->post(route('creditos.reestructurar.store', $credito->id), [
        'fecha_reestructura'       => '2026-09-25',
        'motivo'                   => 'Dificultad_Economica',
        'mora_condonada'           => 3064.84,
        'interes_condonado'        => 0,
        'nuevo_plazo_meses'        => 12,
        'nueva_tasa_interes'       => 7,
        'nueva_fecha_inicio_pagos' => '2026-10-15',
        'numero_resolutivo'        => 'TEST-46-001',
    ])->assertRedirect();

    $baseNueva = round($credito->amortizaciones()
        ->where('estado', 'Pendiente')
        ->sum('capital_esperado'), 2);

    expect($sumaVieja)->toBe(28206.73)
        ->and($baseNueva)->toBe(27177.42)
        ->and(round($sumaVieja - $baseNueva, 2))->toBe(1029.31)
        ->and((float) Reestructuracion::first()->saldo_al_momento)->toBe(27177.42);

    $primeraNueva = $credito->amortizaciones()->where('estado', 'Pendiente')->orderBy('numero_cuota')->first();
    expect((float) $primeraNueva->saldo_insoluto)->toBe(27177.42);

    expect($credito->amortizaciones()->whereBetween('numero_cuota', [4, 24])
        ->where('estado', 'Reestructurada')->count())->toBe(21);
});

test('T2: rechaza la reestructuración cuando el monto resultante es <= 0', function () {
    [$user, $credito] = baseReestructuraTest();
    $this->actingAs($user);

    $credito->amortizaciones()->create([
        'numero_cuota'               => 1,
        'fecha_vencimiento'          => '2026-05-15',
        'saldo_insoluto'             => 5000,
        'capital_esperado'           => 5000,
        'interes_ordinario_esperado' => 0,
        'cuota_fija'                 => 5000,
        'pago_restante'              => 0,
        'estado'                     => 'Pagado',
        'capital_pagado'             => 5000,
        'interes_ordinario_pagado'   => 0,
        'interes_moratorio_pagado'   => 0,
        'interes_moratorio_generado' => 0,
        'moratorio_acumulado'        => 0,
    ]);

    $this->post(route('creditos.reestructurar.store', $credito->id), [
        'fecha_reestructura'       => '2026-09-25',
        'motivo'                   => 'Dificultad_Economica',
        'nuevo_plazo_meses'        => 12,
        'nueva_tasa_interes'       => 7,
        'nueva_fecha_inicio_pagos' => '2026-10-15',
    ])->assertSessionHasErrors('monto');

    expect(Reestructuracion::count())->toBe(0)
        ->and($credito->amortizaciones()->count())->toBe(1);
});

test('T3: guarda en mora_condonada la mora real, no la capturada por el operador', function () {
    [$user, $credito] = baseReestructuraTest();
    $this->actingAs($user);

    $moras = [1389.94, 946.48, 550.40, 178.02]; // Σ = 3064.84
    foreach ($moras as $i => $mora) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $i + 1,
            'fecha_vencimiento'          => '2026-0' . (5 + $i) . '-15',
            'saldo_insoluto'             => 4000 - ($i * 1000),
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 100,
            'cuota_fija'                 => 1100,
            'pago_restante'              => 1100,
            'estado'                     => 'Pendiente',
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => $mora,
            'moratorio_acumulado'        => 0,
        ]);
    }

    $this->post(route('creditos.reestructurar.store', $credito->id), [
        'fecha_reestructura'       => '2026-09-25',
        'motivo'                   => 'Dificultad_Economica',
        'mora_condonada'           => 999.99,   // debe ignorarse
        'interes_condonado'        => 12345,    // debe ignorarse
        'nuevo_plazo_meses'        => 12,
        'nueva_tasa_interes'       => 7,
        'nueva_fecha_inicio_pagos' => '2026-10-15',
    ])->assertRedirect();

    $reestructuracion = Reestructuracion::first();

    // mora_condonada = Σ interes_moratorio_generado (3064.84), NO 999.99
    expect((float) $reestructuracion->mora_condonada)->toBe(3064.84)
        ->and((float) $reestructuracion->saldo_al_momento)->toBe(4400.00);
});

test('T4: mora_condonada descuenta la mora ya pagada', function () {
    [$user, $credito] = baseReestructuraTest();
    $this->actingAs($user);

    $credito->amortizaciones()->create([
        'numero_cuota'               => 1,
        'fecha_vencimiento'          => '2026-05-15',
        'saldo_insoluto'             => 1000,
        'capital_esperado'           => 1000,
        'interes_ordinario_esperado' => 100,
        'cuota_fija'                 => 1100,
        'pago_restante'              => 1100,
        'estado'                     => 'Pendiente',
        'capital_pagado'             => 0,
        'interes_ordinario_pagado'   => 0,
        'interes_moratorio_pagado'   => 300,
        'interes_moratorio_generado' => 1000,
        'moratorio_acumulado'        => 1000,
    ]);

    $this->post(route('creditos.reestructurar.store', $credito->id), [
        'fecha_reestructura'       => '2026-09-25',
        'motivo'                   => 'Dificultad_Economica',
        'mora_condonada'           => 9999,   // debe ignorarse
        'nuevo_plazo_meses'        => 12,
        'nueva_tasa_interes'       => 7,
        'nueva_fecha_inicio_pagos' => '2026-10-15',
    ])->assertRedirect();

    $reestructuracion = Reestructuracion::first();

    // Mora pendiente = generado 1000 − pagado 300 = 700
    expect((float) $reestructuracion->mora_condonada)->toBe(700.00)
        ->and((float) $reestructuracion->saldo_al_momento)->toBe(1100.00);
});
