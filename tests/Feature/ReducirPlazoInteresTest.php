<?php

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;

/**
 * Fixture para reproducir el problema de conciliación de interés
 * al utilizar "Reducir Plazo".
 *
 * Crédito:
 * - $44,670.20
 * - 24 meses
 * - 7% anual
 * - Cuota fija de $2,000
 *
 * La primera cuota:
 * - Capital: $1,739.42
 * - Interés: $260.58
 *
 * Después de pagar la primera cuota quedan $42,930.78 de capital.
 */
function crearCreditoReducirPlazoInteres(): Credito
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Reducir-Plazo-Interes-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Reducir Plazo Interes',
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'             => $modalidad->id,
        'clave_contrato'           => 'TEST-RP-' . uniqid(),
        'monto_otorgado'           => 44670.20,
        'plazo_meses'              => 24,
        'fecha_entrega'            => Carbon::today()->subMonths(1)->toDateString(),
        'tasa_interes_ordinario'   => 7.00,
        'tasa_interes_moratorio'   => 17.50,
        'estatus'                  => 'Activo',
    ]);

    $tasaMensual = 0.00583333; // 7 / 100 / 12

    /*
     * Cuota 1.
     *
     * Ésta será pagada normalmente por el pago de $5,000.
     */
    $credito->amortizaciones()->create([
        'numero_cuota'                 => 1,
        'fecha_vencimiento'            => Carbon::today()->toDateString(),
        'saldo_insoluto'               => 44670.20,
        'capital_esperado'             => 1739.42,
        'interes_ordinario_esperado'   => 260.58,
        'cuota_fija'                   => 2000.00,
        'pago_restante'                => 2000.00,
        'estado'                       => 'Pendiente',
        'capital_pagado'               => 0,
        'interes_ordinario_pagado'     => 0,
        'interes_moratorio_pagado'     => 0,
        'interes_moratorio_generado'   => 0,
        'moratorio_acumulado'          => 0,
    ]);

    /*
     * Cuotas 2 a 24.
     *
     * Se genera la misma tabla de amortización utilizada en
     * las pruebas existentes.
     */
    $principal = 44670.20 - 1739.42; // 42,930.78

    for ($numero = 2; $numero <= 24; $numero++) {

        if ($numero < 24) {
            $interes = round($principal * $tasaMensual, 2);
            $capital = 2000 - $interes;
        } else {
            /*
             * La última cuota absorbe cualquier residuo de capital.
             */
            $capital = round($principal, 2);
            $interes = round(2000 - $capital, 2);
        }

        $credito->amortizaciones()->create([
            'numero_cuota'                 => $numero,
            'fecha_vencimiento'            => Carbon::today()
                ->addMonths($numero - 1)
                ->toDateString(),

            'saldo_insoluto'               => round($principal, 2),
            'capital_esperado'             => round($capital, 2),
            'interes_ordinario_esperado'   => round($interes, 2),
            'cuota_fija'                   => 2000.00,
            'pago_restante'                => 2000.00,
            'estado'                       => 'Pendiente',
            'capital_pagado'               => 0,
            'interes_ordinario_pagado'     => 0,
            'interes_moratorio_pagado'     => 0,
            'interes_moratorio_generado'   => 0,
            'moratorio_acumulado'          => 0,
        ]);

        $principal -= $capital;
    }

    return $credito;
}

/**
 * Crea un usuario operativo válido para registrar el pago.
 */
function crearOperativoReducirPlazoInteres(): User
{
    return User::factory()->create([
        'tipo'              => 'operativo',
        'email_verified_at' => now(),
    ]);
}

/**
 * Reproduce el problema:
 *
 * Al utilizar "Reducir Plazo", el sobrante se aplica al capital de
 * las últimas cuotas. Cuando alcanza para liquidar completamente
 * el capital de una cuota, aplicarAbonoCapital() también marca su
 * interés ordinario como pagado.
 *
 * Sin embargo, ese interés no está respaldado por
 * pagos.aplicado_ordinario.
 *
 * El test mide cuánto interés queda registrado de más.
 */
test('reducir plazo registra interes ordinario sin respaldo en el pago', function () {

    $this->actingAs(crearOperativoReducirPlazoInteres());

    $credito = crearCreditoReducirPlazoInteres();

    /*
     * Pago de $5,000:
     *
     * $2,000 -> cuota 1
     * $3,000 -> sobrante aplicado mediante Reducir Plazo
     */
    $this->post(route('pagos.store', $credito->id), [
        'monto_recibido' => 5000,
        'fecha_pago'     => Carbon::today()->toDateString(),
        'forma_pago'     => 'Efectivo',
        'tipo_abono'     => 'Reducir Plazo',
    ])->assertRedirect();

    /*
     * Recuperamos el pago registrado.
     */
    $pago = Pago::where('credito_id', $credito->id)->first();

    expect($pago)->not->toBeNull();

    /*
     * ---------------------------------------------------------
     * 1. INTERÉS RESPALDADO POR LOS PAGOS
     * ---------------------------------------------------------
     *
     * Éste es el interés que, según la tabla pagos,
     * realmente fue cobrado.
     */
    $interesSegunPagos = round(
        (float) Pago::where('credito_id', $credito->id)
            ->sum('aplicado_ordinario'),
        2
    );

    /*
     * ---------------------------------------------------------
     * 2. INTERÉS REGISTRADO EN LAS AMORTIZACIONES
     * ---------------------------------------------------------
     *
     * Éste es el interés que las cuotas consideran pagado.
     */
    $interesSegunAmortizaciones = round(
        (float) Amortizacion::where('credito_id', $credito->id)
            ->sum('interes_ordinario_pagado'),
        2
    );

    /*
     * ---------------------------------------------------------
     * 3. DIFERENCIA
     * ---------------------------------------------------------
     *
     * Si este número es mayor que cero, tenemos interés
     * marcado como pagado sin que exista dinero registrado
     * en pagos.aplicado_ordinario que lo respalde.
     */
    $interesCobradoDeMas = round(
        $interesSegunAmortizaciones - $interesSegunPagos,
        2
    );

    /*
     * ---------------------------------------------------------
     * 4. CUOTAS LIQUIDADAS POR EL ABONO A CAPITAL
     * ---------------------------------------------------------
     *
     * Esto permite identificar específicamente qué cuotas
     * recibieron el interés "fantasma".
     */
    $cuotasLiquidadasPorAbono = Amortizacion::where(
        'credito_id',
        $credito->id
    )
        ->where(
            'observaciones',
            'Liquidada por abono anticipado a capital'
        )
        ->orderBy('numero_cuota')
        ->get();

    $interesMarcadoPorAbono = round(
        (float) $cuotasLiquidadasPorAbono
            ->sum('interes_ordinario_pagado'),
        2
    );

    /*
     * Mostramos los resultados para medir el efecto exacto
     * en la base de prueba.
     */
    dump([
        'pago_total' => 5000.00,

        'sobrante_aplicado' => (float) $pago->sobrante_aplicado,

        'interes_segun_pagos' => $interesSegunPagos,

        'interes_segun_amortizaciones' => $interesSegunAmortizaciones,

        'interes_cobrado_de_mas' => $interesCobradoDeMas,

        'cuotas_liquidadas_por_abono' =>
            $cuotasLiquidadasPorAbono
                ->pluck('numero_cuota')
                ->values()
                ->all(),

        'interes_marcado_por_abono' => $interesMarcadoPorAbono,
    ]);

    /*
     * ---------------------------------------------------------
     * ASSERTIONS
     * ---------------------------------------------------------
     */

    /*// Debe existir al menos una cuota liquidada por el abono.
    expect($cuotasLiquidadasPorAbono->count())
        ->toBeGreaterThan(0);

    // Esas cuotas tienen interés marcado como pagado.
    expect($interesMarcadoPorAbono)
        ->toBeGreaterThan(0);

    // Las amortizaciones registran más interés que los pagos.
    expect($interesSegunAmortizaciones)
        ->toBeGreaterThan($interesSegunPagos);

    // Por lo tanto, existe interés registrado de más.
    expect($interesCobradoDeMas)
        ->toBeGreaterThan(0);*/
    // El pago normal de la cuota 1 registra correctamente $260.58 de interés.
    expect($interesSegunPagos)->toBe(260.58);

    // Reducir Plazo provoca que las amortizaciones registren $272.19.
    expect($interesSegunAmortizaciones)->toBe(272.19);

    // La diferencia no respaldada por pagos es exactamente $11.61.
    expect($interesCobradoDeMas)->toBe(11.61);

    // El sobrante alcanzó para liquidar completamente la cuota 24.
    expect(
        $cuotasLiquidadasPorAbono
            ->pluck('numero_cuota')
            ->values()
            ->all()
    )->toBe([24]);

    // Los $11.61 adicionales provienen precisamente de esa cuota.
    expect($interesMarcadoPorAbono)->toBe(11.61);
});