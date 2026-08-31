<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Services\CreditService;
use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\Acreditado;
use App\Models\User;
use Carbon\Carbon;

/**
 * Crea un crédito completo con relaciones para tests.
 * Usa User::factory() + Acreditado + ModalidadCrea + Credito.
 */
function crearCreditoCompleto(array $overrides = []): Credito
{
    $user = User::factory()->create(['tipo' => 'operativo']);
    $acreditado = Acreditado::create([
        'nombre_completo' => 'Test Acreditado',
        'municipio' => 'Mérida',
        'sexo' => 'H',
        'rfc' => 'TEST0001',
        'curp' => 'TEST0001HDFXYZ00',
    ]);
    $modalidad = ModalidadCrea::create([
        'nombre' => 'Test',
        'tasa_interes' => 12.00,
        'tasa_moratoria' => 30.00,
    ]);

    return Credito::create(array_merge([
        'acreditado_id' => $acreditado->id,
        'modalidad_id' => $modalidad->id,
        'clave_contrato' => 'TEST-' . uniqid(),
        'monto_otorgado' => 10000,
        'plazo_meses' => 12,
        'fecha_entrega' => Carbon::parse('2026-01-15'),
        'tasa_interes_ordinario' => 12.00,
        'tasa_interes_moratorio' => 30.00,
        'estatus' => 'Activo',
    ], $overrides));
}

beforeEach(function () {
    $this->service = new CreditService();
});

/**
 * TEST 1: PASA
 * Verifica que se generan exactamente 12 amortizaciones para un crédito estándar.
 * Input: $10,000 · 12 meses · 12% anual
 * Esperado: 12 filas en BD
 * Resultado: PASS (12 amortizaciones creadas)
 */
test('1. genera exactamente 12 amortizaciones para 10,000 a 12m 12%', function () {
    $credito = crearCreditoCompleto(['monto_otorgado' => 10000, 'plazo_meses' => 12, 'tasa_interes_ordinario' => 12.00]);
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 12, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );

    expect($credito->amortizaciones()->count())->toBe(12);
});

/**
 * TEST 2: PASA
 * Verifica que la suma de capital_esperado = monto original (±$0.01 por redondeo).
 * Input: mismo caso 1
 * Fórmula: Σ capital_esperado = 10,000
 * Resultado: PASS (suma = 10,000.00 dentro de tolerancia)
 */
test('2. suma de capital_esperado igual a monto original (±0.01)', function () {
    $credito = crearCreditoCompleto(['monto_otorgado' => 10000, 'plazo_meses' => 12, 'tasa_interes_ordinario' => 12.00]);
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 12, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );

    $sumaCapital = $credito->amortizaciones->sum('capital_esperado');
    expect($sumaCapital)->toBeGreaterThanOrEqual(9999.99)
                    ->toBeLessThanOrEqual(10000.01);
});

/**
 * TEST 3: PASA
 * Verifica amortización francesa: cuota fija constante salvo última (ajuste redondeo).
 * Input: mismo caso 1
 * Cuota teórica: 10000 × 0.01/(1-1.01⁻¹²) = 888.49
 * Resultado: PASS (11 cuotas = 888.49, última ≈ 888.39)
 */
test('3. todas las cuotas tienen mismo monto total salvo la última', function () {
    $credito = crearCreditoCompleto(['monto_otorgado' => 10000, 'plazo_meses' => 12, 'tasa_interes_ordinario' => 12.00]);
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 12, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );

    $cuotas = $credito->amortizaciones->pluck('cuota_fija')->toArray();
    $esperado = round(10000 * (0.01 / (1 - pow(1.01, -12))), 2); // ≈ 888.49
    
    for ($i = 0; $i < 11; $i++) {
        expect((float) $cuotas[$i])->toBe($esperado);
    }
    // última puede variar por redondeo
    expect((float) $cuotas[$i])->toBeGreaterThan(0);
});

/**
 * TEST 4: PASA
 * Verifica modalidad Artesanal (tasa 0%): cuota = monto/plazo, sin interés.
 * Input: $10,000 · 12m · 0%
 * Cuota esperada: 10000/12 = 833.33...
 * Resultado: PASS (interés=0 en todas, Σ capital=10,000)
 */
test('4. tasa 0% (Artesanal): cuota = monto ÷ plazo, sin interés', function () {
    $credito = crearCreditoCompleto(['tasa_interes_ordinario' => 0.00, 'tasa_interes_moratorio' => 0.00]);
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 12, 0.00, Carbon::parse('2026-01-15'), 'Artesanal'
    );

    $cuotas = $credito->amortizaciones;
    expect($cuotas->where('interes_ordinario_esperado', '>', 0)->count())->toBe(0);
    expect($cuotas->where('cuota_fija', 833.33)->count())->toBe(11); // 10000/12 = 833.33...
    expect($cuotas->sum('capital_esperado'))->toBeGreaterThanOrEqual(9999.99)
                                        ->toBeLessThanOrEqual(10000.01);
});

/**
 * TEST 5: PASA
 * Verifica modalidad Sustentable: 3 cuotas de gracia con numero_cuota negativo.
 * Input: $10,000 · 12m · 5% · gracia=3
 * Código genera: g=1→-3, g=2→-2, g=3→-1 (línea 30 CreditService)
 * Resultado: PASS (3 filas estado=Gracia, numero_cuota=[-3,-2,-1], capital=interés=cuota=0)
 */
test('5. modalidad Sustentable genera 3 cuotas de gracia con numero_cuota negativo', function () {
    $credito = crearCreditoCompleto();
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 12, 5.00, Carbon::parse('2026-01-15'), 'Sustentable'
    );

    $gracia = $credito->amortizaciones->where('estado', 'Gracia');
    expect($gracia->count())->toBe(3);
    expect($gracia->pluck('numero_cuota')->toArray())->toEqual([-3, -2, -1]);
    expect($gracia->where('capital_esperado', '>', 0)->count())->toBe(0);
    expect($gracia->where('interes_ordinario_esperado', '>', 0)->count())->toBe(0);
});

/**
 * TEST 6: PASA
 * Verifica fecha de primera amortización = fecha_entrega + 1 mes (sin gracia).
 * Input: fecha_entrega = 2026-01-15
 * Esperado: 2026-02-15
 * Resultado: PASS
 */
test('6. primera amortización tiene fecha_vencimiento correcta según fecha de inicio', function () {
    $credito = crearCreditoCompleto(['fecha_entrega' => '2026-01-15']);
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 12, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );

    $primera = $credito->amortizaciones->where('estado', 'Pendiente')->sortBy('numero_cuota')->first();
    expect($primera->fecha_vencimiento)->toBe('2026-02-15');
});

/**
 * TEST 7: FALLA (BUG)
 * Verifica que monto ≤ 0 no genera amortizaciones.
 * Input: monto=0 y monto=-100
 * Comportamiento actual (BUG): CreditService NO valida monto > 0 (líneas 16-23)
 * Genera 12 amortizaciones con capital=0, cuota=0, saldo=0
 * Esperado: Debe lanzar InvalidArgumentException o generar 0 filas
 * Archivo afectado: app/Services/CreditService.php:16-23 (falta guard clause)
 * 
 * TODO: Añadir validación en CreditService:
 *   if ($monto <= 0) throw new \InvalidArgumentException('Monto debe ser > 0');
 */
test('7. monto 0 o negativo no genera amortizaciones', function () {
    $credito = crearCreditoCompleto();
    
    $this->service->generarTablaAmortizacion(
        $credito, 0, 12, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );
    expect($credito->amortizaciones()->count())->toBe(0);

    $credito2 = crearCreditoCompleto();
    $this->service->generarTablaAmortizacion(
        $credito2, -100, 12, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );
    expect($credito2->amortizaciones()->count())->toBe(0);
});

/**
 * TEST 8: FALLA (BUG)
 * Verifica que plazo ≤ 0 no genera amortizaciones (evita DivisionByZeroError).
 * Input: plazo=0
 * Comportamiento actual (BUG): CreditService NO valida plazo > 0
 * Línea 47: $monto / $plazo → DivisionByZeroError
 * Línea 48: fórmula francesa con -$plazo en pow() también falla
 * Esperado: Debe lanzar InvalidArgumentException antes de calcular
 * Archivo afectado: app/Services/CreditService.php:16-23 (falta guard clause)
 * 
 * TODO: Añadir validación en CreditService:
 *   if ($plazo <= 0) throw new \InvalidArgumentException('Plazo debe ser > 0');
 */
test('8. plazo 0 no genera amortizaciones (evita división entre cero)', function () {
    $credito = crearCreditoCompleto();
    
    $this->service->generarTablaAmortizacion(
        $credito, 10000, 0, 12.00, Carbon::parse('2026-01-15'), 'Emprendedores'
    );
    expect($credito->amortizaciones()->count())->toBe(0);
});