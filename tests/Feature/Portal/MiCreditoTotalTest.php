<?php

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;

/**
 * P-07: el "Total a pagar para ponerte al corriente" del portal debe coincidir
 * al centavo con lo que cobra caja (PagoController::resumenVencido).
 *
 * Caja se consulta por su propia pantalla (route('pagos.create')): se suman los
 * total_a_pagar de las cuotas vencidas, que es exactamente lo que ve el operativo.
 */

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Crea un crédito con las cuotas indicadas (misma modalidad/tasas que Miguel).
 * Cada cuota: numero, vence, capital, interes, saldo (opcional), estado y pagos.
 */
function portalP07Credito(array $cuotas): array
{
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);
    $operativo = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);

    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Emprendedores-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Miguel Caamal Dzib',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'CREA-2026-' . uniqid(),
        'monto_otorgado'         => 30000,
        'plazo_meses'            => 24,
        'fecha_entrega'          => '2026-01-15',
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus'                => 'Activo',
    ]);

    foreach ($cuotas as $c) {
        $capPag = $c['capital_pagado'] ?? 0;
        $intPag = $c['interes_pagado'] ?? 0;
        $capital = $c['capital'];
        $interes = $c['interes'] ?? 0;

        $credito->amortizaciones()->create([
            'numero_cuota'               => $c['numero'],
            'fecha_vencimiento'          => $c['vence'],
            'saldo_insoluto'             => $c['saldo'] ?? $capital,
            'capital_esperado'           => $capital,
            'interes_ordinario_esperado' => $interes,
            'cuota_fija'                 => round($capital + $interes, 2),
            'capital_pagado'             => $capPag,
            'interes_ordinario_pagado'   => $intPag,
            'pago_restante'              => round($capital + $interes - $capPag - $intPag, 2),
            'estado'                     => $c['estado'] ?? 'Pendiente',
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado'        => 0,
        ]);
    }

    SolicitudCredito::create([
        'user_id'         => $ciudadano->id,
        'nombre_completo' => 'Miguel Caamal Dzib',
        'estatus'         => 'Aprobada',
        'acreditado_id'   => $acreditado->id,
        'credito_id'      => $credito->id,
    ]);

    return [$ciudadano, $operativo, $credito];
}

/** Props de la pantalla del ciudadano (Mi Crédito). */
function portalP07Props($test, User $ciudadano): array
{
    $props = null;

    $test->actingAs($ciudadano)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];
        });

    return $props;
}

/** Cuotas que ve el operativo en caja (route('pagos.create')). */
function cajaP07Cuotas($test, User $operativo, Credito $credito): array
{
    $props = null;

    $test->actingAs($operativo)
        ->get(route('pagos.create', $credito->id))
        ->assertOk()
        ->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];
        });

    return $props['cuotas_pendientes'];
}

/** Cuotas reales del crédito de Miguel (CREA-2026-101), recortadas a #1..#9. */
function portalP07CuotasMiguel(): array
{
    return [
        ['numero' => 1, 'vence' => '2026-02-15', 'saldo' => 30000.00, 'capital' => 1168.18, 'interes' => 175.00, 'estado' => 'Pagado', 'capital_pagado' => 1168.18, 'interes_pagado' => 175.00],
        ['numero' => 2, 'vence' => '2026-03-15', 'saldo' => 28831.82, 'capital' => 1174.99, 'interes' => 168.19, 'estado' => 'Pagado', 'capital_pagado' => 1174.99, 'interes_pagado' => 168.19],
        ['numero' => 3, 'vence' => '2026-04-15', 'saldo' => 27656.83, 'capital' => 1181.85, 'interes' => 161.33, 'estado' => 'Pagado', 'capital_pagado' => 1181.85, 'interes_pagado' => 161.33],
        ['numero' => 4, 'vence' => '2026-05-15', 'saldo' => 26474.98, 'capital' => 1188.74, 'interes' => 154.44],
        ['numero' => 5, 'vence' => '2026-06-15', 'saldo' => 25286.24, 'capital' => 1195.68, 'interes' => 147.50],
        ['numero' => 6, 'vence' => '2026-07-15', 'saldo' => 24090.56, 'capital' => 1202.65, 'interes' => 140.53],
        ['numero' => 7, 'vence' => '2026-08-15', 'saldo' => 22887.91, 'capital' => 1209.67, 'interes' => 133.51],
        ['numero' => 8, 'vence' => '2026-09-15', 'saldo' => 21678.24, 'capital' => 1216.72, 'interes' => 126.46],
        ['numero' => 9, 'vence' => '2026-10-15', 'saldo' => 20461.52, 'capital' => 1223.82, 'interes' => 119.36],
    ];
}

test('P-07 (a): Miguel con 5 cuotas vencidas; el total y la mora coinciden con caja', function () {
    // Corte del hallazgo P-07: 5 cuotas vencidas (#4..#8).
    Carbon::setTestNow(Carbon::create(2026, 9, 28, 18, 0, 0, 'UTC'));

    [$ciudadano, $operativo, $credito] = portalP07Credito(portalP07CuotasMiguel());

    $portal = portalP07Props($this, $ciudadano);

    expect($portal['credito']['proxima_cuota']['vencida'])->toBeTrue()
        ->and($portal['credito']['proxima_cuota']['cuotas'])->toBe(5)
        ->and($portal['credito']['proxima_cuota']['total'])->toBe(11261.68)
        ->and($portal['credito']['proxima_cuota']['mora'])->toBe(4545.78)
        ->and($portal['credito']['cuotas_vencidas'])->toHaveCount(5);

    // Desglose por cuota (número, fecha, capital, interés, mora, total).
    $desglose = collect($portal['credito']['cuotas_vencidas'])->keyBy('numero');
    expect($desglose[4]['total'])->toBe(3093.47)
        ->and($desglose[5]['total'])->toBe(2633.83)
        ->and($desglose[6]['total'])->toBe(2221.48)
        ->and($desglose[7]['total'])->toBe(1832.73)
        ->and($desglose[8]['total'])->toBe(1480.17);

    // Caja: lo que cobra el operativo por las vencidas (#4..#8).
    $caja         = cajaP07Cuotas($this, $operativo, $credito);
    $cajaVencidas = collect($caja)->whereIn('numero_cuota', [4, 5, 6, 7, 8]);
    $cajaTotal    = round($cajaVencidas->sum(fn ($c) => (float) $c['total_a_pagar']), 2);
    $cajaMora     = round($cajaVencidas->sum(fn ($c) => (float) $c['mora_al_dia']), 2);

    expect($cajaTotal)->toBe(round($portal['credito']['proxima_cuota']['total'], 2))
        ->and($cajaMora)->toBe(round($portal['credito']['proxima_cuota']['mora'], 2));

    // Y cuota por cuota: portal == caja.
    foreach ($portal['credito']['cuotas_vencidas'] as $cuota) {
        $filaCaja = $cajaVencidas->firstWhere('numero_cuota', $cuota['numero']);
        expect(round((float) $filaCaja['total_a_pagar'], 2))->toBe(round($cuota['total'], 2));
    }
});

test('P-07 (b): una sola cuota vencida se ve igual que antes y coincide con caja', function () {
    Carbon::setTestNow(Carbon::create(2026, 9, 28, 18, 0, 0, 'UTC'));

    [$ciudadano, $operativo, $credito] = portalP07Credito([
        ['numero' => 1, 'vence' => '2026-08-19', 'saldo' => 1000.00, 'capital' => 1000.00, 'interes' => 50.00],
        ['numero' => 2, 'vence' => '2026-10-19', 'saldo' => 1000.00, 'capital' => 1000.00, 'interes' => 45.00],
    ]);

    $portal = portalP07Props($this, $ciudadano);
    $prox   = $portal['credito']['proxima_cuota'];

    // 40 días de atraso: mora = 1000 * (17.5/100/360) * 40 = 19.44.
    expect($prox['vencida'])->toBeTrue()
        ->and($prox['numero'])->toBe(1)
        ->and($prox['cuotas'])->toBe(1)
        ->and((float) $prox['capital'])->toBe(1000.00)
        ->and((float) $prox['interes'])->toBe(50.00)
        ->and((float) $prox['mora'])->toBe(19.44)
        ->and((float) $prox['total'])->toBe(1069.44)
        ->and($portal['credito']['cuotas_vencidas'])->toHaveCount(1);

    // Caja cobra exactamente lo mismo por esa única cuota vencida.
    $caja     = cajaP07Cuotas($this, $operativo, $credito);
    $filaCaja = collect($caja)->firstWhere('numero_cuota', 1);

    expect(round((float) $filaCaja['total_a_pagar'], 2))->toBe(1069.44)
        ->and(round((float) $filaCaja['mora_al_dia'], 2))->toBe(19.44);
});

test('P-07 (c): crédito al corriente conserva la próxima cuota y coincide con caja', function () {
    Carbon::setTestNow(Carbon::create(2026, 9, 28, 18, 0, 0, 'UTC'));

    [$ciudadano, $operativo, $credito] = portalP07Credito([
        ['numero' => 1, 'vence' => '2026-10-08', 'saldo' => 1000.00, 'capital' => 1000.00, 'interes' => 50.00],
        ['numero' => 2, 'vence' => '2026-11-08', 'saldo' => 500.00, 'capital' => 500.00, 'interes' => 25.00],
    ]);

    $portal = portalP07Props($this, $ciudadano);
    $prox   = $portal['credito']['proxima_cuota'];

    expect($prox['vencida'])->toBeFalse()
        ->and($prox['numero'])->toBe(1)
        ->and($prox['cuotas'])->toBe(1)
        ->and((float) $prox['capital'])->toBe(1000.00)
        ->and((float) $prox['interes'])->toBe(50.00)
        ->and((float) $prox['mora'])->toBe(0.0)
        ->and((float) $prox['total'])->toBe(1050.00)
        ->and($portal['credito']['cuotas_vencidas'])->toBeEmpty();

    // Caja muestra esa misma próxima cuota como exigible.
    $caja    = cajaP07Cuotas($this, $operativo, $credito);
    $primera = $caja[0];

    expect($primera['numero_cuota'])->toBe(1)
        ->and(round((float) $primera['total_a_pagar'], 2))->toBe(round((float) $prox['total'], 2));
});
