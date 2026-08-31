<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Services\CreditService;
use App\Models\Credito;
use App\Models\Amortizacion;
use App\Models\ModalidadCrea;
use App\Models\Acreditado;
use App\Models\User;
use Carbon\Carbon;

/**
 * Crea un crédito completo con amortizaciones para tests de mora.
 * Usa User::factory() + Acreditado + ModalidadCrea + Credito + tabla amortización.
 * RFC único por test (MORA001, MORA002...) para evitar duplicados.
 * Timezone America/Merida para coincidir con CreditService::actualizarMoraDeCuota().
 */
function crearCreditoConMora(array $overrides = []): Credito
{
    static $rfcCounter = 0;
    $user = User::factory()->create(['tipo' => 'operativo']);
    $rfc = $overrides['rfc'] ?? 'MORA' . str_pad(++$rfcCounter, 3, '0', STR_PAD_LEFT);
    $curp = $overrides['curp'] ?? $rfc . 'HDFXYZ00';
    
    $acreditado = Acreditado::create([
        'nombre_completo' => 'Test Mora',
        'municipio' => 'Mérida',
        'sexo' => 'H',
        'rfc' => $rfc,
        'curp' => $curp,
    ]);
    $modalidad = ModalidadCrea::create([
        'nombre' => 'Emprendedores',
        'tasa_interes' => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $credito = Credito::create(array_merge([
        'acreditado_id' => $acreditado->id,
        'modalidad_id' => $modalidad->id,
        'clave_contrato' => 'MORA-' . uniqid(),
        'monto_otorgado' => 10000,
        'plazo_meses' => 12,
        'fecha_entrega' => Carbon::now('America/Merida')->subMonths(2),
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus' => 'Activo',
    ], $overrides));

    // Generar tabla de amortización simple
    (new CreditService())->generarTablaAmortizacion(
        $credito,
        10000,
        12,
        7.00,
        Carbon::now('America/Merida')->subMonths(2),
        'Emprendedores'
    );

    return $credito;
}

beforeEach(function () {
    $this->service = new CreditService();
});

/**
 * TEST 1: PASA
 * Verifica que se cobra mora cuando la cuota está vencida más de 5 días.
 * Input: Cuota vencida hace 30 días, tasa moratoria 17.5%
 * Fórmula: mora = pago_restante × (17.5/100/360) × 30
 * tasa_diaria = 0.00048611...
 * Esperado: mora > 0
 * Resultado: PASS
 */
test('1. cobra mora cuando la cuota lleva mas de 5 dias vencida', function () {
    $credito = crearCreditoConMora();
    $amortizacion = $credito->amortizaciones()->first();
    
    // Mover vencimiento 30 días atrás (vencida hace 30 días)
    // Usar mismo timezone que el servicio (America/Merida)
    $amortizacion->fecha_vencimiento = Carbon::now('America/Merida')->subDays(30)->format('Y-m-d');
    $amortizacion->save();
    
    $mora = $this->service->actualizarMoraDeCuota($amortizacion->fresh());
    
    expect($mora)->toBeGreaterThan(0);
});

/**
 * TEST 2: PASA
 * Verifica que NO se cobra mora dentro de los 5 días de gracia.
 * Input: Cuota vencida hace 3 días (dentro de gracia)
 * Regla de negocio: 5 días de gracia antes de cobrar mora
 * Esperado: mora == 0
 * Resultado: PASS
 */
test('2. no cobra mora dentro de los 5 dias de gracia', function () {
    $credito = crearCreditoConMora();
    $amortizacion = $credito->amortizaciones()->first();
    
    // Vencida hace 3 días (dentro de gracia)
    // Usar mismo timezone que el servicio (America/Merida)
    $amortizacion->fecha_vencimiento = Carbon::now('America/Merida')->subDays(3)->format('Y-m-d');
    $amortizacion->save();
    
    $mora = $this->service->actualizarMoraDeCuota($amortizacion->fresh());
    
    expect($mora)->toBe(0);
});