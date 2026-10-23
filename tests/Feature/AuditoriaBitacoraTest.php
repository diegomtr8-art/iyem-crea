<?php

use App\Models\Acreditado;
use App\Models\AuditoriaLog;
use App\Models\ComprobacionUso;
use App\Models\Credito;
use App\Models\Desembolso;
use App\Models\ModalidadCrea;
use App\Models\Pago;
use App\Models\SolicitudCredito;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\ModalidadCreaSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * J.11: cada operación que mueve dinero o cambia un crédito deja en auditoria_log quién la hizo,
 * desde qué IP, el campo, el valor anterior y el nuevo, y una descripción con la clave y los montos.
 * Ver docs/AUDITORIA_BITACORA.md.
 */
function bitacoraOperativo(string $nombre = 'Operativo Bitácora'): User
{
    return User::factory()->create(['name' => $nombre, 'tipo' => 'operativo', 'email_verified_at' => now()]);
}

/** Crédito de $12,000 a 12 meses al 7 %, con la primera cuota venciendo hoy. */
function bitacoraCredito(string $estatus = 'Activo'): Credito
{
    $modalidad  = ModalidadCrea::create(['nombre' => 'Bitácora-' . uniqid(), 'tasa_interes' => 7, 'tasa_moratoria' => 17.5]);
    $acreditado = Acreditado::create(['nombre_completo' => 'Persona De Prueba', 'municipio' => 'Mérida']);

    $credito = $acreditado->creditos()->create([
        'modalidad_id'           => $modalidad->id,
        'clave_contrato'         => 'CREA-BIT-' . strtoupper(uniqid()),
        'monto_otorgado'         => 12000,
        'plazo_meses'            => 12,
        'fecha_entrega'          => Carbon::today()->subMonth()->toDateString(),
        'tasa_interes_ordinario' => 7,
        'tasa_interes_moratorio' => 17.5,
        'estatus'                => $estatus,
    ]);

    $i     = 0.07 / 12;
    $cuota = round(12000 * $i / (1 - (1 + $i) ** -12), 2);
    $saldo = 12000;
    for ($n = 1; $n <= 12; $n++) {
        $interes = round($saldo * $i, 2);
        $capital = $n < 12 ? round($cuota - $interes, 2) : round($saldo, 2);
        $credito->amortizaciones()->create([
            'numero_cuota'               => $n,
            'fecha_vencimiento'          => Carbon::today()->addMonths($n - 1)->toDateString(),
            'saldo_insoluto'             => round($saldo, 2),
            'capital_esperado'           => $capital,
            'interes_ordinario_esperado' => $interes,
            'cuota_fija'                 => round($capital + $interes, 2),
            'pago_restante'              => round($capital + $interes, 2),
            'estado'                     => 'Pendiente',
            'capital_pagado'             => 0,
            'interes_ordinario_pagado'   => 0,
            'interes_moratorio_pagado'   => 0,
            'interes_moratorio_generado' => 0,
            'moratorio_acumulado'        => 0,
        ]);
        $saldo -= $capital;
    }

    return $credito;
}

function bitacoraPrimeraCuota(Credito $credito): float
{
    return (float) $credito->amortizaciones()->orderBy('numero_cuota')->value('pago_restante');
}

/** La fila que dejó la operación; sole() falla si hay cero o más de una. */
function filaDeBitacora(string $entidad, int $id, string $campo, ?string $accion = null): AuditoriaLog
{
    return AuditoriaLog::where('entidad', $entidad)->where('entidad_id', $id)->where('campo_modificado', $campo)
        ->when($accion, fn ($q) => $q->where('accion', $accion))
        ->sole();
}

function esperarQuienYDesde(AuditoriaLog $fila, User $usuario): void
{
    expect($fila->usuario_id)->toBe($usuario->id)
        ->and($fila->usuario_nombre)->toBe($usuario->name)
        ->and($fila->ip_address)->toBe('127.0.0.1');
}

test('registrar un pago deja el monto, el desglose y la clave del crédito', function () {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();
    $monto   = bitacoraPrimeraCuota($credito);

    $this->actingAs($usuario)->post(route('pagos.store', $credito), [
        'monto_recibido' => $monto, 'fecha_pago' => today()->toDateString(), 'forma_pago' => 'Efectivo',
    ])->assertRedirect();

    $pago = Pago::sole();
    $fila = filaDeBitacora('Pago', $pago->id, 'monto_recibido');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->accion)->toBe('created')
        ->and($fila->valor_anterior)->toBeNull()
        ->and($fila->valor_nuevo)->toBe(number_format($monto, 2, '.', ''))
        ->and($fila->descripcion)->toContain($pago->folio, $credito->clave_contrato, AuditoriaLog::dinero($monto), 'Efectivo')
        ->toContain('capital ' . AuditoriaLog::dinero($pago->aplicado_capital), 'interés ' . AuditoriaLog::dinero($pago->aplicado_ordinario));
});

test('cancelar un pago deja Activo → Cancelado con el monto y el motivo', function () {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();
    $this->actingAs($usuario)->post(route('pagos.store', $credito), [
        'monto_recibido' => bitacoraPrimeraCuota($credito), 'fecha_pago' => today()->toDateString(), 'forma_pago' => 'Efectivo',
    ]);
    $pago = Pago::sole();

    $this->post(route('pagos.cancelar', $pago), ['motivo_cancelacion' => 'Se capturó dos veces'])->assertRedirect();

    $fila = filaDeBitacora('Pago', $pago->id, 'cancelado');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->accion)->toBe('updated')
        ->and($fila->valor_anterior)->toBe('Activo')
        ->and($fila->valor_nuevo)->toBe('Cancelado')
        ->and($fila->descripcion)->toContain($pago->folio, $credito->clave_contrato, AuditoriaLog::dinero($pago->monto_recibido), 'Se capturó dos veces');
});

test('reducir plazo o cuota deja el plan de pagos antes y después', function (string $tipo) {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();
    $cuota   = bitacoraPrimeraCuota($credito);

    // La cuota de hoy más un sobrante de tres cuotas
    $this->actingAs($usuario)->post(route('pagos.store', $credito), [
        'monto_recibido' => round($cuota * 4, 2), 'fecha_pago' => today()->toDateString(),
        'forma_pago' => 'Transferencia', 'tipo_abono' => $tipo,
    ])->assertRedirect();

    $pago = Pago::sole();
    $fila = filaDeBitacora('Credito', $credito->id, 'plan_de_pagos');
    esperarQuienYDesde($fila, $usuario);
    expect($pago->tipo_abono)->toBe($tipo)
        ->and($fila->valor_anterior)->toBe('12 cuotas pendientes, la siguiente de ' . AuditoriaLog::dinero($cuota))
        ->and($fila->descripcion)->toContain($credito->clave_contrato, $tipo, $pago->folio, AuditoriaLog::dinero($pago->sobrante_aplicado));

    preg_match('/^(\d+) cuotas pendientes, la siguiente de \$([\d,.]+)$/', $fila->valor_nuevo, $despues);
    [$cuotas, $siguiente] = [(int) $despues[1], (float) str_replace(',', '', $despues[2])];

    if ($tipo === 'Reducir Plazo') {
        expect($cuotas)->toBeLessThan(11)->and($siguiente)->toBe($cuota);
    } else {
        expect($cuotas)->toBe(11)->and($siguiente)->toBeLessThan($cuota);
    }
})->with(['Reducir Plazo', 'Reducir Cuota']);

test('la condonación total deja cuánto se condonó y por qué, además del cambio de estatus', function () {
    $usuario   = bitacoraOperativo();
    $credito   = bitacoraCredito();
    $pendiente = round((float) $credito->amortizaciones()->sum(DB::raw('capital_esperado + interes_ordinario_esperado')), 2);

    $this->actingAs($usuario)->post(route('creditos.condonar', $credito), [
        'motivo' => 'Fallecimiento del titular', 'tipo_causa' => 'Fallecimiento',
    ])->assertRedirect();

    $fila = filaDeBitacora('Credito', $credito->id, 'saldo_condonado');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->valor_anterior)->toBe(number_format($pendiente, 2, '.', ''))
        ->and($fila->valor_nuevo)->toBe('0.00')
        ->and($fila->descripcion)->toContain($credito->clave_contrato, 'Fallecimiento del titular', 'capital ' . AuditoriaLog::dinero(12000), Pago::sole()->folio);

    $estatus = filaDeBitacora('Credito', $credito->id, 'estatus');
    esperarQuienYDesde($estatus, $usuario);
    expect([$estatus->valor_anterior, $estatus->valor_nuevo])->toBe(['Activo', 'Liquidado']);
});

test('la condonación formal deja el tipo, el desglose y el motivo', function (string $tipo, float $capital) {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();

    $this->actingAs($usuario)->post(route('creditos.condonacion-formal.store', $credito), [
        'tipo' => $tipo, 'monto_condonado_capital' => $capital, 'monto_condonado_intereses' => 0, 'monto_condonado_mora' => 0,
        'motivo' => 'Acuerdo del comité', 'numero_acuerdo' => 'AC-07', 'fecha_autorizacion' => today()->toDateString(),
    ])->assertRedirect();

    $fila = filaDeBitacora('Credito', $credito->id, 'condonacion_formal');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->valor_anterior)->toBeNull()
        ->and($fila->valor_nuevo)->toBe($tipo)
        ->and($fila->descripcion)->toContain($credito->clave_contrato, 'Capital ' . AuditoriaLog::dinero($capital), 'Acuerdo del comité', 'AC-07');
})->with([
    'parcial de capital' => ['Parcial_Capital', 500],
    'total'              => ['Total', 0],
]);

test('la reestructuración deja el plan anterior y el nuevo con saldo, mora condonada y resolutivo', function () {
    // En SQLite el estado de la cuota es un CHECK sin "Reestructurada" (en MySQL lo agrega una migración)
    if (DB::getDriverName() === 'sqlite') {
        Schema::table('amortizaciones', fn (Blueprint $t) => $t->string('estado')->default('Pendiente')->change());
    }
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito('Moroso');

    $this->actingAs($usuario)->post(route('creditos.reestructurar.store', $credito), [
        'fecha_reestructura' => today()->toDateString(), 'motivo' => 'Dificultad_Economica', 'nuevo_plazo_meses' => 18,
        'nueva_tasa_interes' => 5, 'nueva_fecha_inicio_pagos' => today()->addMonth()->toDateString(), 'numero_resolutivo' => 'RES-2026-01',
    ])->assertRedirect();

    $fila = filaDeBitacora('Credito', $credito->id, 'reestructuracion');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->valor_anterior)->toBe('12 cuotas pendientes al 7.00 % (Moroso)')
        ->and($fila->valor_nuevo)->toStartWith('18 cuotas de $')->toEndWith('al 5 %')
        ->and($fila->descripcion)->toContain($credito->clave_contrato, 'Saldo reestructurado', 'mora condonada', 'RES-2026-01');

    $estatus = filaDeBitacora('Credito', $credito->id, 'estatus');
    expect([$estatus->valor_anterior, $estatus->valor_nuevo])->toBe(['Moroso', 'Activo']);
});

test('el desembolso deja el monto como valor nuevo', function () {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();

    $this->actingAs($usuario)->post(route('creditos.desembolso.store', $credito), [
        'fecha_desembolso' => today()->toDateString(), 'monto_desembolsado' => 12000, 'forma_desembolso' => 'Transferencia',
    ])->assertRedirect();

    $fila = filaDeBitacora('Desembolso', Desembolso::sole()->id, 'monto_desembolsado');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->accion)->toBe('created')
        ->and($fila->valor_nuevo)->toBe('12000.00')
        ->and($fila->descripcion)->toContain($credito->clave_contrato, '$12,000.00', 'Transferencia');
});

test('crear un crédito deja el monto, el plazo y las tasas', function () {
    $usuario = bitacoraOperativo();
    $this->actingAs($usuario);

    $credito = bitacoraCredito();

    $fila = filaDeBitacora('Credito', $credito->id, 'monto_otorgado');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->accion)->toBe('created')
        ->and($fila->valor_nuevo)->toBe('12000.00')
        ->and($fila->descripcion)->toContain($credito->clave_contrato, '$12,000.00', '12 meses', '7.00 %', '17.50 %');
});

test('cambiar estatus, monto, plazo, tasas, fechas o claves del crédito deja una fila por campo', function () {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();
    [$clave, $modalidad] = [$credito->clave_contrato, $credito->modalidad_id];
    $otra    = ModalidadCrea::create(['nombre' => 'Otra modalidad', 'tasa_interes' => 5, 'tasa_moratoria' => 12.5]);

    // No hay pantalla que edite estos datos; el observer los registra venga de donde venga el cambio
    $this->actingAs($usuario);
    $credito->update([
        'estatus' => 'Moroso', 'monto_otorgado' => 12500, 'plazo_meses' => 18, 'tasa_interes_ordinario' => 6,
        'tasa_interes_moratorio' => 15, 'fecha_entrega' => '2026-02-01', 'fecha_contrato' => '2026-01-20',
        'modalidad_id' => $otra->id, 'clave_contrato' => 'CREA-NUEVA-01', 'numero_contrato_oficial' => 'OF-99',
    ]);

    $esperado = [
        'estatus'                 => ['Activo', 'Moroso'],
        'monto_otorgado'          => ['12000.00', '12500.00'],
        'plazo_meses'             => ['12', '18'],
        'tasa_interes_ordinario'  => ['7.00', '6.00'],
        'tasa_interes_moratorio'  => ['17.50', '15.00'],
        'fecha_entrega'           => [today()->subMonth()->toDateString(), '2026-02-01'],
        'fecha_contrato'          => [null, '2026-01-20'],
        'modalidad_id'            => [(string) $modalidad, (string) $otra->id],
        'clave_contrato'          => [$clave, 'CREA-NUEVA-01'],
        'numero_contrato_oficial' => [null, 'OF-99'],
    ];

    foreach ($esperado as $campo => [$antes, $despues]) {
        $fila = filaDeBitacora('Credito', $credito->id, $campo, 'updated');
        esperarQuienYDesde($fila, $usuario);
        expect([$campo, $fila->valor_anterior, $fila->valor_nuevo])->toBe([$campo, $antes, $despues])
            // Con la clave de antes, para que la búsqueda por clave encuentre todo el historial
            ->and($fila->descripcion)->toContain($clave);
    }
});

test('eliminar un acreditado deja antes lo que se borra en cascada', function () {
    $usuario = bitacoraOperativo();
    $credito = bitacoraCredito();
    $monto   = bitacoraPrimeraCuota($credito);
    $this->actingAs($usuario)->post(route('pagos.store', $credito), [
        'monto_recibido' => $monto, 'fecha_pago' => today()->toDateString(), 'forma_pago' => 'Efectivo',
    ]);

    $this->delete(route('acreditados.destroy', $credito->acreditado_id))->assertRedirect();

    expect(Credito::count())->toBe(0)->and(Pago::count())->toBe(0);

    $fila = AuditoriaLog::where('entidad', 'Credito')->where('accion', 'deleted')->sole();
    esperarQuienYDesde($fila, $usuario);
    expect($fila->entidad_id)->toBe($credito->id)
        ->and($fila->valor_anterior)->toBe('12000.00')
        ->and($fila->valor_nuevo)->toBeNull()
        ->and($fila->descripcion)->toContain($credito->clave_contrato, '1 pago(s) activos por ' . AuditoriaLog::dinero($monto));

    $acreditado = filaDeBitacora('Acreditado', $credito->acreditado_id, 'nombre_completo');
    expect($acreditado->accion)->toBe('deleted')
        ->and($acreditado->valor_anterior)->toBe('Persona De Prueba')
        ->and($acreditado->descripcion)->toContain($credito->clave_contrato);
});

test('aprobar o rechazar una comprobación deja el cambio de estatus sin error 500', function (string $ruta, array $datos, string $estatus, string $texto) {
    $this->seed([ModalidadCreaSeeder::class, TestDataSeeder::class]);
    $usuario      = bitacoraOperativo();
    $credito      = Credito::first();
    $comprobacion = ComprobacionUso::create([
        'credito_id' => $credito->id, 'solicitud_id' => SolicitudCredito::first()->id, 'acreditado_id' => $credito->acreditado_id,
        'fecha_desembolso' => today()->toDateString(), 'fecha_limite_comprobacion' => today()->addMonth()->toDateString(), 'estatus' => 'En_Revision',
    ]);

    $this->actingAs($usuario)->post(route($ruta, $comprobacion), $datos)->assertRedirect();

    $fila = filaDeBitacora('ComprobacionUso', $comprobacion->id, 'estatus');
    esperarQuienYDesde($fila, $usuario);
    expect($fila->accion)->toBe('updated')
        ->and([$fila->valor_anterior, $fila->valor_nuevo])->toBe(['En_Revision', $estatus])
        ->and($fila->descripcion)->toContain($credito->clave_contrato, $texto)
        ->and($comprobacion->fresh()->estatus)->toBe($estatus);
})->with([
    'aprobar'  => ['comprobaciones.aprobar', ['monto_comprobado' => 8000.5], 'Aprobada', 'Monto comprobado: $8,000.50'],
    'rechazar' => ['comprobaciones.rechazar', ['observaciones_operativo' => 'Faltan las facturas'], 'Rechazada', 'Faltan las facturas'],
]);

test('la bitácora no se edita, no se borra y no acepta acciones fuera del enum', function () {
    AuditoriaLog::registrar('Credito', 1, 'updated', 'estatus', 'Activo', 'Moroso', 'Fila original');
    $fila = AuditoriaLog::sole();

    expect(fn () => $fila->update(['valor_nuevo' => 'Liquidado']))->toThrow(LogicException::class)
        ->and(fn () => $fila->delete())->toThrow(LogicException::class)
        ->and(fn () => AuditoriaLog::registrar('ComprobacionUso', 1, 'aprobada'))->toThrow(InvalidArgumentException::class)
        ->and(AuditoriaLog::count())->toBe(1)
        ->and($fila->fresh()->valor_nuevo)->toBe('Moroso');
});

test('/auditoria filtra por entidad, acción, usuario y fechas de Mérida, y busca por clave aunque la fila no la tenga', function () {
    $this->withoutVite();
    $admin = bitacoraOperativo('Administración');
    $admin->assignRole(Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']));
    $ana  = bitacoraOperativo('Ana Cajera');
    $beto = bitacoraOperativo('Beto Cobranza');

    [$credito, $otro, $pago, $desembolso] = Credito::withoutEvents(function () use ($ana) {
        $credito = bitacoraCredito();
        $otro    = bitacoraCredito();
        $pago    = Pago::withoutEvents(fn () => Pago::create([
            'credito_id' => $credito->id, 'acreditado_id' => $credito->acreditado_id, 'folio' => 'REC-VIEJO-1',
            'monto_recibido' => 100, 'forma_pago' => 'Efectivo', 'fecha_pago' => today()->toDateString(), 'registrado_por' => $ana->id,
        ]));
        $desembolso = Desembolso::create([
            'credito_id' => $credito->id, 'fecha_desembolso' => today()->toDateString(), 'monto_desembolsado' => 12000, 'forma_desembolso' => 'Cheque',
            'registrado_por' => $ana->id,
        ]);

        return [$credito, $otro, $pago, $desembolso];
    });

    $fila = function (string $utc, User $quien, array $datos) {
        $this->travelTo(Carbon::parse($utc, 'UTC'));
        $this->actingAs($quien);
        AuditoriaLog::registrar(...$datos);
    };
    // Las descripciones con el formato viejo no traen la clave del crédito
    $fila('2026-10-10 03:00:00', $ana, ['Pago', $pago->id, 'created', null, null, null, "A: Pago REC-VIEJO-1 registrado. Monto: $100.00 — Crédito ID: {$credito->id}"]);
    $fila('2026-10-10 15:00:00', $beto, ['Desembolso', $desembolso->id, 'created', null, null, null, 'B: Desembolso registrado']);
    $fila('2026-10-11 12:00:00', $beto, ['Credito', $otro->id, 'updated', 'estatus', 'Activo', 'Moroso', "C: Crédito {$otro->clave_contrato}"]);
    $fila('2026-10-12 12:00:00', $ana, ['Acreditado', 99, 'deleted', 'nombre_completo', 'Alguien', null, 'D: Acreditado borrado']);
    $this->travelBack();

    $ver = function (array $filtros = []) use ($admin) {
        $props = $this->actingAs($admin)->get(route('auditoria.index', $filtros))->assertOk()->viewData('page')['props'];

        return [collect($props['logs']['data'])->map(fn ($l) => substr($l['descripcion'], 0, 1))->sort()->values()->all(), $props];
    };

    [$todas, $props] = $ver();
    expect($todas)->toBe(['A', 'B', 'C', 'D'])
        ->and($props['totales'])->toBe(['total' => 4, 'creados' => 2, 'actualizados' => 1, 'eliminados' => 1])
        ->and(collect($props['usuarios'])->pluck('name')->all())->toBe(['Ana Cajera', 'Beto Cobranza'])
        ->and(collect($props['entidades'])->all())->toBe(['Acreditado', 'Credito', 'Desembolso', 'Pago'])
        // 03:00 UTC del 10/10 son las 21:00 del 9/10 en Mérida
        ->and(collect($props['logs']['data'])->firstWhere('entidad', 'Pago')['fecha'])->toBe('09/10/2026 21:00:00');

    expect($ver(['entidad' => 'Pago'])[0])->toBe(['A'])
        ->and($ver(['accion' => 'deleted'])[0])->toBe(['D'])
        ->and($ver(['usuario_id' => $beto->id])[0])->toBe(['B', 'C'])
        ->and($ver(['fecha_desde' => '2026-10-10', 'fecha_hasta' => '2026-10-10'])[0])->toBe(['B'])
        ->and($ver(['fecha_hasta' => '2026-10-09'])[0])->toBe(['A'])
        // El pago y el desembolso son de ese crédito aunque su descripción no diga la clave
        ->and($ver(['buscar' => $credito->clave_contrato])[0])->toBe(['A', 'B'])
        ->and($ver(['buscar' => 'Beto'])[0])->toBe(['B', 'C']);
});
