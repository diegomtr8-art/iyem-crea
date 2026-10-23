<?php

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\User;
use App\Services\CreditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Lanzamiento interno — motor de pagos (paquete C).
 * Crédito Emprendedores de $12,000 a 12 meses, 7% ordinaria, 17.5% moratoria.
 */
function crearCreditoLanzamiento(string $fechaEntrega = '2026-05-05', string $estatus = 'Activo'): Credito
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Emprendedores-' . uniqid(),
        'tasa_interes'   => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Lanzamiento',
        'municipio'       => 'Mérida',
        'sexo'            => 'M',
        'rfc'             => 'LANZ' . strtoupper(substr(uniqid(), -9)),
        'curp'            => 'LANZ' . strtoupper(substr(uniqid(), -12)),
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'LANZ-' . uniqid(),
        'monto_otorgado'         => 12000,
        'plazo_meses'            => 12,
        'fecha_entrega'          => $fechaEntrega,
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus'                => $estatus,
    ]);

    app(CreditService::class)->generarTablaAmortizacion(
        $credito, 12000, 12, 7.00, Carbon::parse($fechaEntrega), $modalidad->nombre
    );

    return $credito;
}

function pagar(Credito $credito, float $monto, string $fecha, array $extra = [])
{
    return test()->post(route('pagos.store', $credito), array_merge([
        'monto_recibido' => $monto,
        'fecha_pago'     => $fecha,
        'forma_pago'     => 'Efectivo',
    ], $extra));
}

/** Conciliación A2: lo que dicen los pagos activos contra lo que registran las cuotas. */
function diferenciasA2(Credito $credito): array
{
    $pagos = Pago::where('credito_id', $credito->id)->where('cancelado', false);
    $amort = Amortizacion::where('credito_id', $credito->id);

    return [
        'capital'   => round((float) (clone $pagos)->sum('aplicado_capital') - (float) (clone $amort)->sum('capital_pagado'), 2),
        'ordinario' => round((float) (clone $pagos)->sum('aplicado_ordinario') - (float) (clone $amort)->sum('interes_ordinario_pagado'), 2),
        'mora'      => round((float) (clone $pagos)->sum('aplicado_mora') - (float) (clone $amort)->sum('interes_moratorio_pagado'), 2),
    ];
}

beforeEach(function () {
    // 2026-07-05: la cuota 1 (vence 2026-06-05) lleva 30 días vencida.
    Carbon::setTestNow(Carbon::create(2026, 7, 5, 12, 0, 0, 'UTC'));
    $this->actingAs(User::factory()->create(['tipo' => 'operativo']));
});

afterEach(function () {
    Carbon::setTestNow();
});

// ── C1 ────────────────────────────────────────────────────────────────────

test('C1: la mora ya pagada no se vuelve a cobrar en el siguiente pago', function () {
    $credito = crearCreditoLanzamiento();
    $cuota1  = $credito->amortizaciones()->where('numero_cuota', 1)->first();

    // Mora de la cuota 1: 12,000 × (17.5% / 360) × 30 días = $175.00. Interés: $70.00.
    expect((float) $cuota1->interes_ordinario_esperado)->toBe(70.0);

    // Pago 1: mora 175 + interés 70 + capital 500 = $745.
    pagar($credito, 745, '2026-07-05')->assertSessionHasNoErrors();
    $pago1 = Pago::latest('id')->first();
    expect((float) $pago1->aplicado_mora)->toBe(175.0)
        ->and((float) $pago1->aplicado_ordinario)->toBe(70.0)
        ->and((float) $pago1->aplicado_capital)->toBe(500.0);

    // Pago 2 el mismo día: la mora ya está pagada, todo va a capital.
    pagar($credito, 200, '2026-07-05')->assertSessionHasNoErrors();
    $pago2 = Pago::latest('id')->first();
    expect($pago2->id)->not->toBe($pago1->id)
        ->and((float) $pago2->aplicado_mora)->toBe(0.0)
        ->and((float) $pago2->aplicado_capital)->toBe(200.0)
        ->and((float) $cuota1->fresh()->interes_moratorio_pagado)->toBe(175.0)
        ->and(diferenciasA2($credito))->toBe(['capital' => 0.0, 'ordinario' => 0.0, 'mora' => 0.0]);
});

test('C1: la pantalla de pago y el tope de "ponerse al día" no incluyen mora ya pagada', function () {
    $credito = crearCreditoLanzamiento();
    pagar($credito, 745, '2026-07-05')->assertSessionHasNoErrors();

    $this->get(route('pagos.create', $credito))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cuotas_pendientes.0.numero_cuota', 1)
            ->where('cuotas_pendientes.0.mora_al_dia', 0));

    // Sin mora pendiente, ya no aplica el tope de "ponerse al día": se puede
    // pagar el resto de la cuota 1 y adelantar la 2 (con tipo de abono).
    pagar($credito, 1500, '2026-07-05', ['tipo_abono' => 'Adelantado'])->assertSessionHasNoErrors();
});

// ── C2 ────────────────────────────────────────────────────────────────────

test('C2: dos envíos iguales seguidos dejan un solo pago', function () {
    $credito = crearCreditoLanzamiento('2026-06-20'); // sin cuotas vencidas

    pagar($credito, 300, '2026-07-05')->assertSessionHasNoErrors();
    pagar($credito, 300, '2026-07-05')
        ->assertSessionHasErrors(['monto_recibido' => 'Ya se registró un pago igual hace un momento. Revise el historial de pagos antes de volver a capturarlo.']);

    expect(Pago::where('credito_id', $credito->id)->count())->toBe(1);

    // Pasados 2 minutos, un pago igual sí se acepta (puede ser legítimo).
    Carbon::setTestNow(now()->addMinutes(3));
    pagar($credito, 300, '2026-07-05')->assertSessionHasNoErrors();
    expect(Pago::where('credito_id', $credito->id)->count())->toBe(2);
});

test('C2: no se registran pagos en un crédito liquidado', function () {
    $credito = crearCreditoLanzamiento('2026-06-20', 'Liquidado');

    pagar($credito, 300, '2026-07-05')->assertForbidden();
    expect(Pago::count())->toBe(0);
});

// ── C3 ────────────────────────────────────────────────────────────────────

test('C3: solo se cancela el último pago activo y la conciliación A2 queda en cero', function () {
    $credito = crearCreditoLanzamiento();
    pagar($credito, 745, '2026-07-05')->assertSessionHasNoErrors();
    $pago1 = Pago::latest('id')->first();
    pagar($credito, 200, '2026-07-05')->assertSessionHasNoErrors();
    $pago2 = Pago::latest('id')->first();

    // El primero no se puede cancelar mientras el segundo siga activo.
    $this->post(route('pagos.cancelar', $pago1), ['motivo_cancelacion' => 'Error de captura'])
        ->assertSessionHasErrors('motivo_cancelacion');
    expect($pago1->fresh()->cancelado)->toBeFalse();

    // Del más reciente al más antiguo sí.
    $this->post(route('pagos.cancelar', $pago2), ['motivo_cancelacion' => 'Error de captura'])
        ->assertSessionHasNoErrors();
    expect(diferenciasA2($credito))->toBe(['capital' => 0.0, 'ordinario' => 0.0, 'mora' => 0.0]);

    $this->post(route('pagos.cancelar', $pago1), ['motivo_cancelacion' => 'Error de captura'])
        ->assertSessionHasNoErrors();
    expect($pago1->fresh()->cancelado)->toBeTrue()
        ->and(diferenciasA2($credito))->toBe(['capital' => 0.0, 'ordinario' => 0.0, 'mora' => 0.0])
        ->and((float) Amortizacion::where('credito_id', $credito->id)->sum('capital_pagado'))->toBe(0.0);
});

test('C3: un pago ya cancelado no se cancela otra vez', function () {
    $credito = crearCreditoLanzamiento('2026-06-20');
    pagar($credito, 300, '2026-07-05');
    $pago = Pago::latest('id')->first();

    $this->post(route('pagos.cancelar', $pago), ['motivo_cancelacion' => 'Error'])->assertSessionHasNoErrors();
    $this->post(route('pagos.cancelar', $pago), ['motivo_cancelacion' => 'Error'])->assertStatus(422);
});

test('C3: el registro de $0 de una condonación no se puede cancelar', function () {
    $credito = crearCreditoLanzamiento('2026-06-20');

    $this->post(route('creditos.condonar', $credito), [
        'motivo'     => 'Fallecimiento del acreditado',
        'tipo_causa' => 'Fallecimiento',
    ])->assertSessionHasNoErrors();

    $registro = Pago::where('credito_id', $credito->id)->sole();
    expect($registro->forma_pago)->toBe('Condonación');

    $this->post(route('pagos.cancelar', $registro), ['motivo_cancelacion' => 'Prueba'])
        ->assertSessionHasErrors('motivo_cancelacion');
    expect($registro->fresh()->cancelado)->toBeFalse()
        ->and($credito->fresh()->estatus)->toBe('Liquidado');
});

// ── C4 ────────────────────────────────────────────────────────────────────

test('C4: el cron marca Moroso y regresa a Activo respetando los días de gracia', function () {
    // Cuota 1 vencida hace 30 días → Moroso.
    $vencido = crearCreditoLanzamiento('2026-05-05');
    // Cuota 1 vencida hace 3 días (dentro de la gracia) → sigue Activo.
    $enGracia = crearCreditoLanzamiento('2026-06-02');
    // Moroso sin cuotas vencidas → regresa a Activo.
    $alCorriente = crearCreditoLanzamiento('2026-06-20', 'Moroso');
    // Liquidado con cuota vencida → no se toca.
    $liquidado = crearCreditoLanzamiento('2026-05-05', 'Liquidado');
    // Cancelado sin cuotas vencidas → no se toca.
    $cancelado = crearCreditoLanzamiento('2026-06-20', 'Cancelado');

    Artisan::call('crea:update-moratorio');

    expect($vencido->fresh()->estatus)->toBe('Moroso')
        ->and($enGracia->fresh()->estatus)->toBe('Activo')
        ->and($alCorriente->fresh()->estatus)->toBe('Activo')
        ->and($liquidado->fresh()->estatus)->toBe('Liquidado')
        ->and($cancelado->fresh()->estatus)->toBe('Cancelado');
});

test('C4: al registrar un pago, una cuota dentro de los días de gracia no vuelve Moroso el crédito', function () {
    $credito = crearCreditoLanzamiento('2026-06-02'); // cuota 1 vencida hace 3 días

    pagar($credito, 100, '2026-07-05')->assertSessionHasNoErrors();

    expect($credito->fresh()->estatus)->toBe('Activo');
});

// ── C5 ────────────────────────────────────────────────────────────────────

test('C5: el monto acepta máximo 2 decimales y tiene un tope', function () {
    $credito = crearCreditoLanzamiento('2026-06-20');

    pagar($credito, 100.555, '2026-07-05')->assertSessionHasErrors('monto_recibido');
    pagar($credito, 1000000.01, '2026-07-05')->assertSessionHasErrors('monto_recibido');
    expect(Pago::count())->toBe(0);
});

test('C5: la fecha no puede ser anterior a la entrega ni al último pago', function () {
    $credito = crearCreditoLanzamiento('2026-06-20');

    pagar($credito, 100, '2026-06-19')->assertSessionHasErrors('fecha_pago');

    pagar($credito, 100, '2026-07-03')->assertSessionHasNoErrors();
    pagar($credito, 120, '2026-07-01')
        ->assertSessionHasErrors(['fecha_pago' => 'La fecha no puede ser anterior al último pago registrado (03/07/2026). Los pagos se capturan en orden cronológico.']);
    pagar($credito, 120, '2026-07-03')->assertSessionHasNoErrors();
    pagar($credito, 120, '2026-07-06')->assertSessionHasErrors('fecha_pago'); // futura

    expect(Pago::where('credito_id', $credito->id)->count())->toBe(2);
});

// ── C6 ────────────────────────────────────────────────────────────────────

test('C6: un crédito que inicia el 31 de enero vence a fin de cada mes sin saltarse meses', function () {
    $credito = crearCreditoLanzamiento('2026-01-31');

    $fechas = $credito->amortizaciones()->orderBy('numero_cuota')->limit(4)->get()
        ->map(fn ($a) => $a->fecha_vencimiento->format('Y-m-d'))->all();

    expect($fechas)->toBe(['2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31']);
});
