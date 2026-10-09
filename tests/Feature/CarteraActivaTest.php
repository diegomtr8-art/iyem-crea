<?php

use App\Jobs\CalcularCarteraActiva;
use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Crea un crédito con el estatus dado y una cuota por cada [estado, pago_restante].
 *
 * @param  array<int, array{0:string, 1:float}>  $cuotas
 */
function creditoParaCartera(string $estatus, array $cuotas): void
{
    $modalidad = ModalidadCrea::create([
        'nombre'         => 'Modalidad-' . uniqid(),
        'tasa_interes'   => 0.00,
        'tasa_moratoria' => 0.00,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Acreditado ' . uniqid(),
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'CAR-' . uniqid(),
        'monto_otorgado'         => 3000,
        'plazo_meses'            => count($cuotas),
        'fecha_entrega'          => Carbon::today()->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => $estatus,
    ]);

    foreach ($cuotas as $i => [$estado, $restante]) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $i + 1,
            'fecha_vencimiento'          => Carbon::today()->addMonths($i + 1)->toDateString(),
            'saldo_insoluto'             => 3000,
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 0,
            'cuota_fija'                 => 1000,
            'pago_restante'              => $restante,
            'estado'                     => $estado,
        ]);
    }
}

beforeEach(function () {
    $this->withoutVite();
    $this->travelTo(Carbon::parse('2026-10-02 13:00', 'UTC')); // 7:00 en Mérida
});

test('el job guarda en caché lo que falta por pagar de los créditos vigentes', function () {
    creditoParaCartera('Activo', [['Pendiente', 1000], ['Parcial', 400], ['Pagado', 0]]);
    creditoParaCartera('Moroso', [['Pendiente', 1000], ['Condonado', 1000]]);
    creditoParaCartera('Liquidado', [['Pendiente', 5000]]); // ya no está vigente: no cuenta

    CalcularCarteraActiva::dispatchSync();

    expect(Cache::get(CalcularCarteraActiva::CLAVE_CACHE))->toBe([
        'total'        => 2400.0,
        'creditos'     => 2,
        'calculado_en' => now()->toIso8601String(),
    ]);
});

test('la tarjeta no cambia aunque cambien los datos, hasta el siguiente cálculo', function () {
    creditoParaCartera('Activo', [['Pendiente', 1000]]);
    CalcularCarteraActiva::dispatchSync();
    $usuario = User::factory()->create(['tipo' => 'operativo']);

    // Se paga la cuota, pero el Job no se vuelve a correr
    DB::table('amortizaciones')->update(['pago_restante' => 0, 'estado' => 'Pagado']);

    $this->actingAs($usuario)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('cartera_activa.total', 1000));

    // Al día siguiente corre el Job y la tarjeta se actualiza
    $this->travel(1)->days();
    CalcularCarteraActiva::dispatchSync();

    $this->actingAs($usuario)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('cartera_activa.total', 0)
            ->where('cartera_activa.calculado_en', now()->toIso8601String()));
});

test('si el job no ha corrido, el panel no calcula el total al vuelo', function () {
    creditoParaCartera('Activo', [['Pendiente', 1000]]);

    $this->actingAs(User::factory()->create(['tipo' => 'operativo']))->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('cartera_activa', null));

    expect(Cache::has(CalcularCarteraActiva::CLAVE_CACHE))->toBeFalse();
});

test('el job está programado una vez al día a las 7:00 hora Mérida', function () {
    $evento = collect(app(Schedule::class)->events())
        ->first(fn ($e) => $e->description === CalcularCarteraActiva::class);

    expect($evento)->not->toBeNull()
        ->and($evento->expression)->toBe('0 7 * * *')
        ->and($evento->timezone)->toBe('America/Merida');
});
