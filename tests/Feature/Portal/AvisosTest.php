<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Crea un ciudadano con crédito propio y una cuota por cada desfase de días
 * respecto a hoy (Mérida). Las claves son el numero_cuota.
 *
 * @param  array<int, array{0:int, 1:string}>  $cuotas  numero => [desfaseDias, estado]
 */
function ciudadanoConCuotas(string $prefijo, array $cuotas): array
{
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $modalidad = ModalidadCrea::create([
        'nombre'         => "Modalidad-{$prefijo}-" . uniqid(),
        'tasa_interes'   => 0.00,
        'tasa_moratoria' => 0.00,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => "Acreditado {$prefijo}",
        'municipio'       => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => "AV-{$prefijo}-" . uniqid(),
        'monto_otorgado'         => 6000,
        'plazo_meses'            => count($cuotas),
        'fecha_entrega'          => Carbon::today('America/Merida')->toDateString(),
        'tasa_interes_ordinario' => 0,
        'tasa_interes_moratorio' => 0,
        'estatus'                => 'Activo',
    ]);

    SolicitudCredito::create([
        'user_id'       => $user->id,
        'estatus'       => 'Aprobada',
        'acreditado_id' => $acreditado->id,
        'credito_id'    => $credito->id,
    ]);

    foreach ($cuotas as $numero => [$desfase, $estado]) {
        $credito->amortizaciones()->create([
            'numero_cuota'               => $numero,
            'fecha_vencimiento'          => Carbon::today('America/Merida')->addDays($desfase)->toDateString(),
            'saldo_insoluto'             => 6000,
            'capital_esperado'           => 1000,
            'interes_ordinario_esperado' => 0,
            'cuota_fija'                 => 1000,
            'pago_restante'              => $estado === 'Pagado' ? 0 : 1000,
            'estado'                     => $estado,
        ]);
    }

    return compact('user', 'credito');
}

/** Props de la página Inertia indexadas por numero_cuota. */
function cuotasDeAvisos($response): array
{
    return collect($response->original->getData()['page']['props']['cuotas'])
        ->keyBy('numero_cuota')
        ->all();
}

beforeEach(function () {
    // 02:00 UTC = 20:00 del día anterior en Mérida: la fecha UTC y la de Mérida difieren.
    // Se congela en UTC (zona de la app) para no ocultar desfases de zona horaria.
    $this->travelTo(Carbon::parse('2026-10-01 02:00', 'UTC'));
});

test('clasifica cada cuota pendiente según su vencimiento y los días de gracia', function () {
    $a = ciudadanoConCuotas('A', [
        1 => [-6, 'Pendiente'],
        2 => [-5, 'Parcial'],
        3 => [-3, 'Pendiente'],
        4 => [0, 'Pendiente'],
        5 => [10, 'Pendiente'],
        6 => [15, 'Pendiente'],
        7 => [16, 'Pendiente'],
    ]);

    $response = $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();
    $cuotas = cuotasDeAvisos($response);

    expect($cuotas[1])->toMatchArray(['aviso' => 'vencida', 'dias' => -6])
        ->and($cuotas[2])->toMatchArray(['aviso' => 'gracia', 'dias' => -5])
        ->and($cuotas[3])->toMatchArray(['aviso' => 'gracia', 'dias' => -3])
        ->and($cuotas[4])->toMatchArray(['aviso' => 'proxima', 'dias' => 0])
        ->and($cuotas[5])->toMatchArray(['aviso' => 'proxima', 'dias' => 10])
        ->and($cuotas[6])->toMatchArray(['aviso' => 'proxima', 'dias' => 15])
        ->and($cuotas[7])->toMatchArray(['aviso' => 'pendiente', 'dias' => 16]);
});

test('las cuotas vienen ordenadas por fecha de vencimiento', function () {
    $a = ciudadanoConCuotas('A', [
        1 => [20, 'Pendiente'],
        2 => [3, 'Pendiente'],
        3 => [-2, 'Pendiente'],
    ]);

    $response = $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();
    $orden = array_column($response->original->getData()['page']['props']['cuotas'], 'numero_cuota');

    expect($orden)->toBe([3, 2, 1]);
});

test('una cuota que vence exactamente hoy aparece como próxima con 0 días', function () {
    $a = ciudadanoConCuotas('A', [1 => [0, 'Pendiente']]);

    $response = $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();

    expect(cuotasDeAvisos($response)[1])->toMatchArray(['aviso' => 'proxima', 'dias' => 0]);
});

test('las cuotas pagadas o condonadas no aparecen', function () {
    $a = ciudadanoConCuotas('A', [
        1 => [-10, 'Pagado'],
        2 => [5, 'Condonado'],
        3 => [5, 'Pendiente'],
    ]);

    $response = $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();

    expect(array_keys(cuotasDeAvisos($response)))->toBe([3]);
});

test('sin cuotas próximas no hay ninguna marcada como próxima', function () {
    $a = ciudadanoConCuotas('A', [1 => [40, 'Pendiente']]);

    $response = $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();
    $cuotas = cuotasDeAvisos($response);

    expect($cuotas)->toHaveCount(1)
        ->and(collect($cuotas)->where('aviso', 'proxima'))->toBeEmpty();
});

test('un ciudadano sin crédito ve la pantalla sin cuotas', function () {
    $sinCredito = User::factory()->create(['tipo' => 'ciudadano']);
    SolicitudCredito::create(['user_id' => $sinCredito->id, 'estatus' => 'Borrador']);

    $response = $this->actingAs($sinCredito)->get(route('portal.avisos'))->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['credito'])->toBeNull()
        ->and($props['cuotas'])->toBe([]);
});

test('el ciudadano solo ve las cuotas de su propio crédito', function () {
    $a = ciudadanoConCuotas('A', [1 => [5, 'Pendiente']]);
    $b = ciudadanoConCuotas('B', [1 => [7, 'Pendiente'], 2 => [9, 'Pendiente']]);

    $response = $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();
    $props = $response->original->getData()['page']['props'];

    expect($props['credito']['clave_contrato'])->toBe($a['credito']->clave_contrato)
        ->and($props['cuotas'])->toHaveCount(1)
        ->and($props['cuotas'][0]['dias'])->toBe(5);
});

test('consultar avisos no modifica amortizaciones ni créditos', function () {
    $a = ciudadanoConCuotas('A', [1 => [-20, 'Pendiente'], 2 => [-3, 'Pendiente'], 3 => [5, 'Pendiente']]);

    $antes = [DB::table('amortizaciones')->get()->toArray(), DB::table('creditos')->get()->toArray()];

    $this->actingAs($a['user'])->get(route('portal.avisos'))->assertOk();

    expect([DB::table('amortizaciones')->get()->toArray(), DB::table('creditos')->get()->toArray()])->toEqual($antes);
});

test('un invitado es redirigido al login', function () {
    $this->get(route('portal.avisos'))->assertRedirect(route('login'));
});

test('un usuario operativo no usa la ruta del portal', function () {
    $operativo = User::factory()->create(['tipo' => 'operativo']);

    $this->actingAs($operativo)->get(route('portal.avisos'))->assertRedirect(route('dashboard'));
});
