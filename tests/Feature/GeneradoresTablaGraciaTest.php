<?php

use App\Http\Controllers\CobranzaJuridicaController;
use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\ExpedienteJuridico;
use App\Models\ModalidadCrea;
use App\Models\User;
use App\Services\CreditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * A.12: Generación de la gracia de Sustentable en los generadores de la tabla.
 *
 * El reporte A.6 encontró que la tabla de amortización se construye en cuatro
 * lugares y solo CreditService crea las 3 filas de gracia (−3, −2, −1).
 * Estas pruebas lo demuestran con el mismo caso en cada generador:
 * $20,000 · 12 meses · 7 % · entrega 2026-01-15.
 *
 * ReestructuracionController no se prueba aquí: no crea créditos y no aplica
 * prórroga (ver docs/auditoria/A12_pruebas_gracia.md).
 *
 * Las pruebas con ->skip() fallan hoy por el hallazgo A.6. Cuando se corrija
 * el generador, se quita el skip y deben pasar sin cambiar nada más.
 */

const GRACIA_MONTO = 20000.00;
const GRACIA_PLAZO = 12;
const GRACIA_TASA = 7.00;
const GRACIA_FECHA_ENTREGA = '2026-01-15';
const GRACIA_SKIP = 'Hallazgo A.6: este generador no crea las filas de gracia de Sustentable';

beforeEach(function () {
    // En SQLite el enum de amortizaciones.estado se queda con los valores de la
    // migración original: las que agregan 'Reestructurada' y 'Gracia' usan
    // MODIFY COLUMN y solo corren en MySQL. Sin esto, cualquier fila 'Gracia'
    // revienta por el CHECK aunque el generador esté bien.
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA ignore_check_constraints = ON');
    }
});

afterEach(function () {
    // La conexión en memoria se reutiliza entre pruebas: no dejar el PRAGMA
    // prendido para los demás archivos.
    if (DB::getDriverName() === 'sqlite') {
        DB::statement('PRAGMA ignore_check_constraints = OFF');
    }
});

function graciaModalidad(string $nombre): ModalidadCrea
{
    return ModalidadCrea::create([
        'nombre'         => $nombre,
        'tasa_interes'   => GRACIA_TASA,
        'tasa_moratoria' => 17.50,
    ]);
}

function graciaOperativo(): User
{
    return User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);
}

function graciaCreditoSinTabla(ModalidadCrea $modalidad): Credito
{
    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Gracia',
        'municipio'       => 'Mérida',
    ]);

    return $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'A12-' . uniqid(),
        'monto_otorgado'         => GRACIA_MONTO,
        'plazo_meses'            => GRACIA_PLAZO,
        'fecha_entrega'          => GRACIA_FECHA_ENTREGA,
        'tasa_interes_ordinario' => GRACIA_TASA,
        'tasa_interes_moratorio' => 17.50,
        'estatus'                => 'Activo',
    ]);
}

/** Generador 1: CreditService (lo usa la aprobación de solicitudes del operativo). */
function graciaPorCreditService(string $nombreModalidad): Credito
{
    $modalidad = graciaModalidad($nombreModalidad);
    $credito = graciaCreditoSinTabla($modalidad);

    (new CreditService())->generarTablaAmortizacion(
        $credito, GRACIA_MONTO, GRACIA_PLAZO, GRACIA_TASA, Carbon::parse(GRACIA_FECHA_ENTREGA), $modalidad->nombre
    );

    return $credito;
}

/** Generador 2: AcreditadoController@store (alta directa de expediente, POST /acreditados). */
function graciaPorAcreditadoController(string $nombreModalidad): Credito
{
    $modalidad = graciaModalidad($nombreModalidad);

    test()->actingAs(graciaOperativo())
        ->post(route('acreditados.store'), [
            'nombre_completo' => 'Prueba Gracia',
            'municipio'       => 'Mérida',
            'sexo'            => 'H',
            'rfc'             => 'GRAC000101AAA',
            'curp'            => 'GRAC000101HYNRRR01',
            // El controlador lee estas llaves sin ??, el formulario siempre las manda
            'correo'           => null,
            'domicilio_fiscal' => null,
            'regimen_fiscal'   => null,
            'clave_pago'       => null,
            'modalidad_id'    => $modalidad->id,
            'plazo_meses'     => GRACIA_PLAZO,
            'fecha_entrega'   => GRACIA_FECHA_ENTREGA,
            'clave_contrato'  => 'A12-' . uniqid(),
            'monto_otorgado'  => GRACIA_MONTO,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    return Credito::latest('id')->firstOrFail();
}

/**
 * Generador 3: CobranzaJuridicaController@update con estatus Reestructurada.
 * Sus rutas están comentadas en routes/web.php (módulo desactivado), así que
 * se llama al controlador directamente. La "fecha de entrega" de la nueva
 * tabla es nueva_fecha_inicio.
 */
function graciaPorCobranzaJuridica(string $nombreModalidad): Credito
{
    $modalidad = graciaModalidad($nombreModalidad);
    $credito = graciaCreditoSinTabla($modalidad);
    $usuario = graciaOperativo();

    $juridico = ExpedienteJuridico::create([
        'credito_id'           => $credito->id,
        'acreditado_id'        => $credito->acreditado_id,
        'usuario_id'           => $usuario->id,
        'fecha_envio_juridico' => '2026-01-10',
        'estatus'              => 'Pendiente',
    ]);

    $request = Request::create('/juridico/' . $juridico->id, 'PUT', [
        'estatus'            => 'Reestructurada',
        'nuevo_monto'        => GRACIA_MONTO,
        'nuevo_plazo_meses'  => GRACIA_PLAZO,
        'nueva_fecha_inicio' => GRACIA_FECHA_ENTREGA,
    ]);

    app(CobranzaJuridicaController::class)->update($request, $juridico);

    return $credito->fresh();
}

/** Lo que debe tener la tabla de un crédito Sustentable. */
function graciaEsperaConGracia(Credito $credito): void
{
    $filas = $credito->amortizaciones()->orderBy('numero_cuota')->get();

    $gracia = $filas->where('estado', 'Gracia');
    expect($gracia)->toHaveCount(3)
        ->and($gracia->pluck('numero_cuota')->map(fn ($n) => (int) $n)->values()->all())->toBe([-3, -2, -1]);

    foreach ($gracia as $fila) {
        expect((float) $fila->saldo_insoluto)->toBe(GRACIA_MONTO)
            ->and((float) $fila->capital_esperado)->toBe(0.0)
            ->and((float) $fila->interes_ordinario_esperado)->toBe(0.0)
            ->and((float) $fila->cuota_fija)->toBe(0.0)
            ->and((float) $fila->pago_restante)->toBe(0.0);
    }

    graciaEsperaCuotasNormales($filas, 4);
}

/** Lo que debe tener la tabla de un crédito sin gracia (Artesanal). */
function graciaEsperaSinGracia(Credito $credito): void
{
    $filas = $credito->amortizaciones()->orderBy('numero_cuota')->get();

    expect($filas->where('estado', 'Gracia'))->toHaveCount(0)
        ->and($filas->where('numero_cuota', '<', 1))->toHaveCount(0);

    graciaEsperaCuotasNormales($filas, 1);
}

function graciaEsperaCuotasNormales($filas, int $mesesHastaPrimera): void
{
    $normales = $filas->where('numero_cuota', '>=', 1)->values();

    expect($normales->pluck('numero_cuota')->map(fn ($n) => (int) $n)->all())->toBe(range(1, GRACIA_PLAZO));

    $esperada = Carbon::parse(GRACIA_FECHA_ENTREGA)->addMonths($mesesHastaPrimera)->toDateString();
    expect(Carbon::parse($normales->first()->fecha_vencimiento)->toDateString())->toBe($esperada);

    expect(round((float) $normales->sum('capital_esperado'), 2))->toBe(GRACIA_MONTO);
}

dataset('nombres sustentable', ['Sustentable', 'sustentable', 'SUSTENTABLE']);

// --- CreditService ---------------------------------------------------------

test('CreditService: Sustentable crea las 3 filas de gracia', function (string $nombre) {
    graciaEsperaConGracia(graciaPorCreditService($nombre));
})->with('nombres sustentable');

test('CreditService: Artesanal no crea gracia', function () {
    graciaEsperaSinGracia(graciaPorCreditService('Artesanal'));
});

// --- AcreditadoController --------------------------------------------------

test('AcreditadoController: Sustentable crea las 3 filas de gracia', function (string $nombre) {
    graciaEsperaConGracia(graciaPorAcreditadoController($nombre));
})->with('nombres sustentable')->skip(GRACIA_SKIP);

test('AcreditadoController: Artesanal no crea gracia', function () {
    graciaEsperaSinGracia(graciaPorAcreditadoController('Artesanal'));
});

// --- CobranzaJuridicaController --------------------------------------------

test('CobranzaJuridicaController: Sustentable crea las 3 filas de gracia', function (string $nombre) {
    graciaEsperaConGracia(graciaPorCobranzaJuridica($nombre));
})->with('nombres sustentable')->skip(GRACIA_SKIP);

test('CobranzaJuridicaController: Artesanal no crea gracia', function () {
    graciaEsperaSinGracia(graciaPorCobranzaJuridica('Artesanal'));
});
