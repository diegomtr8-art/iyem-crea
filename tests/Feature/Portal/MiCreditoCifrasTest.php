<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;

/**
 * 4.10 — Mi Crédito con cifras consistentes (C-04, P-08).
 *
 * C-04: la tarjeta de próxima cuota y la tabla de amortización deben usar el
 * mismo criterio: del día 1 al 5 de atraso "en gracia"; del día 6 en adelante
 * "vencida". El umbral sale de config('credito.dias_gracia_mora').
 *
 * P-08: "Total pagado" se calcula en MiCredito.vue sumando credito.pagos, que
 * son los pagos activos (no cancelados) y la misma fuente que la pestaña
 * "Pagos realizados". El caso de Miguel en TestDataSeeder es dato de prueba:
 * sus cuotas #1-#3 figuran Pagado sin filas en `pagos`; corresponde a D.13 /
 * el seeder. La prueba (c) demuestra que un pago real de caja sí se refleja.
 */
beforeEach(function () {
    // 18:00 UTC = 12:00 en Mérida: la fecha UTC y la de Mérida coinciden.
    $this->travelTo(Carbon::parse('2026-10-21 18:00:00', 'UTC'));
});

afterEach(function () {
    Carbon::setTestNow();
});

/**
 * Crédito de prueba con las cuotas indicadas.
 * Cada cuota: numero, vence, capital, interes, saldo, estado, pagos.
 */
function portalCifrasCredito(string $prefijo, array $cuotas): array
{
    $hoy = Carbon::now('America/Merida')->startOfDay();

    $ciudadano = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);
    $operativo = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);

    $modalidad = ModalidadCrea::create([
        'nombre' => 'Cifras-'.$prefijo.'-'.uniqid(),
        'tasa_interes' => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Acreditado Cifras '.$prefijo,
        'municipio' => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id' => $modalidad->id,
        'clave_contrato' => 'CIFRAS-'.$prefijo.'-'.uniqid(),
        'monto_otorgado' => 10000,
        'plazo_meses' => count($cuotas),
        'fecha_entrega' => $hoy->copy()->subMonths(count($cuotas))->toDateString(),
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus' => 'Activo',
    ]);

    foreach ($cuotas as $c) {
        $capital = (float) ($c['capital'] ?? 1000);
        $interes = (float) ($c['interes'] ?? 0);
        $capPag = (float) ($c['capital_pagado'] ?? 0);
        $intPag = (float) ($c['interes_pagado'] ?? 0);

        $credito->amortizaciones()->create([
            'numero_cuota' => $c['numero'],
            'fecha_vencimiento' => $c['vence'],
            'saldo_insoluto' => (float) ($c['saldo'] ?? 5000),
            'capital_esperado' => $capital,
            'interes_ordinario_esperado' => $interes,
            'cuota_fija' => round($capital + $interes, 2),
            'pago_restante' => round($capital + $interes - $capPag - $intPag, 2),
            'estado' => $c['estado'] ?? 'Pendiente',
            'capital_pagado' => $capPag,
            'interes_ordinario_pagado' => $intPag,
            'interes_moratorio_pagado' => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado' => 0,
        ]);
    }

    SolicitudCredito::create([
        'user_id' => $ciudadano->id,
        'estatus' => 'Aprobada',
        'acreditado_id' => $acreditado->id,
        'credito_id' => $credito->id,
    ]);

    return [$ciudadano, $operativo, $credito];
}

/** Props de la pantalla del ciudadano (Mi Crédito). */
function portalCifrasProps($test, User $ciudadano): array
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

test('C-04 (a): con 3 días de atraso la cuota está en gracia en tarjeta y tabla', function () {
    $hoy = Carbon::now('America/Merida')->startOfDay();

    [$ciudadano] = portalCifrasCredito('G3', [
        ['numero' => 1, 'vence' => $hoy->copy()->subDays(3)->toDateString()],
        ['numero' => 2, 'vence' => $hoy->copy()->addDays(10)->toDateString()],
    ]);

    $props = portalCifrasProps($this, $ciudadano);
    $prox = $props['credito']['proxima_cuota'];

    expect($prox['estado'])->toBe('gracia')
        ->and($prox['vencida'])->toBeFalse()
        ->and($prox['dias_mora'])->toBe(3)
        ->and($props['credito']['tabla'][0]['estado_visual'])->toBe('en_gracia')
        ->and($props['credito']['tabla'][1]['estado_visual'])->toBe('pendiente')
        ->and($props['dias_gracia'])->toBe((int) config('credito.dias_gracia_mora'));
});

test('C-04 (b): con 7 días de atraso la cuota está vencida en tarjeta y tabla', function () {
    $hoy = Carbon::now('America/Merida')->startOfDay();

    [$ciudadano] = portalCifrasCredito('V7', [
        ['numero' => 1, 'vence' => $hoy->copy()->subDays(7)->toDateString()],
        ['numero' => 2, 'vence' => $hoy->copy()->addDays(10)->toDateString()],
    ]);

    $props = portalCifrasProps($this, $ciudadano);
    $prox = $props['credito']['proxima_cuota'];

    expect($prox['estado'])->toBe('vencida')
        ->and($prox['vencida'])->toBeTrue()
        ->and($prox['dias_mora'])->toBe(7)
        ->and($prox['mora'])->toBeGreaterThan(0)
        ->and($props['credito']['tabla'][0]['estado_visual'])->toBe('vencido');
});

test('C-04: la frontera son los días de gracia configurados', function (int $dias, string $estado, string $visual) {
    $hoy = Carbon::now('America/Merida')->startOfDay();

    [$ciudadano] = portalCifrasCredito('F'.$dias, [
        ['numero' => 1, 'vence' => $hoy->copy()->subDays($dias)->toDateString()],
    ]);

    $props = portalCifrasProps($this, $ciudadano);

    expect($props['credito']['proxima_cuota']['estado'])->toBe($estado)
        ->and($props['credito']['tabla'][0]['estado_visual'])->toBe($visual);
})->with([
    'día 5' => [5, 'gracia', 'en_gracia'],
    'día 6' => [6, 'vencida', 'vencido'],
]);

test('C-04: la etiqueta respeta config(credito.dias_gracia_mora)', function () {
    config(['credito.dias_gracia_mora' => 3]);

    $hoy = Carbon::now('America/Merida')->startOfDay();

    [$ciudadano] = portalCifrasCredito('CFG', [
        ['numero' => 1, 'vence' => $hoy->copy()->subDays(4)->toDateString()],
    ]);

    $props = portalCifrasProps($this, $ciudadano);

    // Con 3 días de gracia, 4 días de atraso ya es vencida (no hay un 5 escrito en el código).
    expect($props['dias_gracia'])->toBe(3)
        ->and($props['credito']['proxima_cuota']['estado'])->toBe('vencida')
        ->and($props['credito']['tabla'][0]['estado_visual'])->toBe('vencido');
});

test('P-08 (c): un pago registrado por caja aparece en Total pagado', function () {
    $hoy = Carbon::now('America/Merida')->startOfDay();

    [$ciudadano, $operativo, $credito] = portalCifrasCredito('P8', [
        ['numero' => 1, 'vence' => $hoy->copy()->addDays(5)->toDateString(), 'capital' => 2000, 'saldo' => 4000],
        ['numero' => 2, 'vence' => $hoy->copy()->addDays(35)->toDateString(), 'capital' => 2000, 'saldo' => 2000],
    ]);

    // Antes del pago: no hay pagos, por eso "Total pagado" vale $0.00.
    $antes = portalCifrasProps($this, $ciudadano);
    expect($antes['credito']['pagos'])->toBeEmpty();

    // Pago real por caja: mismo endpoint que usa el operativo.
    $this->actingAs($operativo)->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 500,
        'fecha_pago' => $hoy->toDateString(),
        'forma_pago' => 'Efectivo',
        'observaciones' => 'Pago de prueba P-08',
    ])->assertRedirect();

    // Un pago cancelado no debe sumar al total.
    Pago::create([
        'credito_id' => $credito->id,
        'acreditado_id' => $credito->acreditado_id,
        'folio' => Pago::generarFolio(),
        'monto_recibido' => 999,
        'fecha_pago' => $hoy->toDateString(),
        'forma_pago' => 'Efectivo',
        'registrado_por' => $operativo->id,
        'cancelado' => true,
    ]);

    $despues = portalCifrasProps($this, $ciudadano);
    $pagos = collect($despues['credito']['pagos']);

    // "Total pagado" en MiCredito.vue es la suma de este mismo arreglo, que también
    // alimenta la pestaña "Pagos realizados": ambos quedan consistentes.
    $totalPagado = round($pagos->sum(fn ($p) => (float) $p['monto_recibido']), 2);

    expect($pagos)->toHaveCount(1)
        ->and((float) $pagos->first()['monto_recibido'])->toBe(500.0)
        ->and($totalPagado)->toBe(500.0);
});
