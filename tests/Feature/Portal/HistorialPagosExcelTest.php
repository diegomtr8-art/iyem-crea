<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Crea un ciudadano con su solicitud vinculada a un crédito propio,
 * dos pagos vigentes y uno cancelado.
 */
function ciudadanoConCredito(string $prefijo): array
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
        'clave_contrato'         => "D5-{$prefijo}-" . uniqid(),
        'monto_otorgado'         => 6000,
        'plazo_meses'            => 3,
        'fecha_entrega'          => Carbon::today()->toDateString(),
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

    $cajero = User::factory()->create(['tipo' => 'operativo']);

    $pago = fn (string $folio, bool $cancelado = false) => Pago::create([
        'credito_id'         => $credito->id,
        'acreditado_id'      => $acreditado->id,
        'folio'              => $folio,
        'monto_recibido'     => 2000,
        'aplicado_mora'      => 0,
        'aplicado_ordinario' => 0,
        'aplicado_capital'   => 2000,
        'forma_pago'         => 'Efectivo',
        'fecha_pago'         => Carbon::today()->toDateString(),
        'cancelado'          => $cancelado,
        'registrado_por'     => $cajero->id,
    ]);

    $vigentes  = [$pago("{$prefijo}-001")->folio, $pago("{$prefijo}-002")->folio];
    $cancelado = $pago("{$prefijo}-CANC", true)->folio;

    return compact('user', 'credito', 'vigentes', 'cancelado');
}

/** Devuelve los valores de la columna A (folios) a partir de la fila 6. */
function foliosDelExcel(string $contenido): array
{
    $ruta = tempnam(sys_get_temp_dir(), 'd5') . '.xlsx';
    file_put_contents($ruta, $contenido);

    $sheet = IOFactory::load($ruta)->getActiveSheet();
    $folios = [];
    for ($row = 6; $row <= $sheet->getHighestRow(); $row++) {
        $valor = $sheet->getCell("A{$row}")->getValue();
        if ($valor !== null && $valor !== '') {
            $folios[] = $valor;
        }
    }
    @unlink($ruta);

    return $folios;
}

test('el ciudadano descarga solo sus pagos vigentes', function () {
    $a = ciudadanoConCredito('A');
    $b = ciudadanoConCredito('B');

    $response = $this->actingAs($a['user'])
        ->get(route('portal.credito.pagos.excel', $a['credito']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $folios = foliosDelExcel($response->streamedContent());

    expect($folios)->toBe($a['vigentes'])
        ->not->toContain($a['cancelado'])
        ->not->toContain(...$b['vigentes'])
        ->not->toContain($b['cancelado']);
});

test('el ciudadano no puede descargar el historial de otro acreditado cambiando el ID', function () {
    $a = ciudadanoConCredito('A');
    $b = ciudadanoConCredito('B');

    $this->actingAs($a['user'])
        ->get(route('portal.credito.pagos.excel', $b['credito']))
        ->assertForbidden();
});

test('un ciudadano sin crédito recibe 403', function () {
    $b = ciudadanoConCredito('B');
    $sinCredito = User::factory()->create(['tipo' => 'ciudadano']);
    SolicitudCredito::create(['user_id' => $sinCredito->id, 'estatus' => 'Borrador']);

    $this->actingAs($sinCredito)
        ->get(route('portal.credito.pagos.excel', $b['credito']))
        ->assertForbidden();
});

test('un invitado es redirigido al login', function () {
    $a = ciudadanoConCredito('A');

    $this->get(route('portal.credito.pagos.excel', $a['credito']))
        ->assertRedirect(route('login'));
});

test('un usuario operativo no usa la ruta del portal', function () {
    $a = ciudadanoConCredito('A');
    $operativo = User::factory()->create(['tipo' => 'operativo']);

    $this->actingAs($operativo)
        ->get(route('portal.credito.pagos.excel', $a['credito']))
        ->assertRedirect(route('dashboard'));
});
