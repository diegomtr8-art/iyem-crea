<?php

use App\Models\Acreditado;
use App\Models\AuditoriaLog;
use App\Models\ComprobacionUso;
use App\Models\Credito;
use App\Models\Desembolso;
use App\Models\DocumentoSolicitud;
use App\Models\Pago;
use App\Models\SolicitudCredito;
use App\Models\User;
use Database\Seeders\ModalidadCreaSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/**
 * Revisión de acceso a las rutas del grupo operativo (J.16, ver docs/SEGURIDAD_ADMIN.md).
 * Solo se comprueba el código de respuesta, con usuarios de prueba y una base en memoria.
 */

/** Un operativo de prueba con el rol indicado (o sin rol). */
function operativoConRol(?string $rol): User
{
    $usuario = User::factory()->create(['tipo' => 'operativo']);

    if ($rol) {
        $usuario->assignRole($rol);
    }

    return $usuario;
}

/**
 * Registros reales para cada parámetro de las rutas: Laravel los busca antes de revisar el rol,
 * así que con un id que no existe la respuesta sería 404 y no se sabría si el rol se revisa.
 */
function parametrosDeRutas(): array
{
    Storage::fake('local');
    test()->seed([ModalidadCreaSeeder::class, TestDataSeeder::class]);

    $credito     = Credito::first();
    $solicitud   = SolicitudCredito::first();
    $registrador = operativoConRol(null);
    $ciudadano   = User::where('tipo', 'ciudadano')->first();

    $desembolso = Desembolso::create([
        'credito_id' => $credito->id, 'fecha_desembolso' => now()->toDateString(), 'monto_desembolsado' => 10000,
        'forma_desembolso' => 'Transferencia', 'registrado_por' => $registrador->id, 'comprobante_ruta' => 'desembolsos/prueba.pdf',
    ]);
    Storage::disk('local')->put('desembolsos/prueba.pdf', '%PDF-1.4 prueba');

    // Algunas pantallas responden 403 por el estado del registro (crédito no liquidado, solicitud no aprobada...).
    // Para que el 403 que se mide sea por permisos, esas rutas reciben un registro en el estado que esperan.
    $liquidado = Credito::where('id', '!=', $credito->id)->first();
    $liquidado->update(['estatus' => 'Liquidado']);
    $aprobada  = SolicitudCredito::where('id', '!=', $solicitud->id)->first();
    $aprobada->update(['estatus' => 'Aprobada']);
    $enRevision = SolicitudCredito::whereNotIn('id', [$solicitud->id, $aprobada->id])->first();
    $enRevision->update(['estatus' => 'En_Revision']);
    $comprobacion = ComprobacionUso::create([
        'credito_id' => $credito->id, 'solicitud_id' => $solicitud->id, 'acreditado_id' => $credito->acreditado_id,
        'fecha_desembolso' => now()->toDateString(), 'fecha_limite_comprobacion' => now()->addMonth()->toDateString(),
    ]);
    $mensaje = $ciudadano->mensajes()->create(['asunto' => 'Asunto de prueba', 'mensaje' => 'Mensaje de prueba']);

    return [
        'acreditado'   => Acreditado::first()->id,
        'credito'      => $credito->id,
        'pago'         => Pago::first()->id,
        'desembolso'   => $desembolso->id,
        'comprobacion' => $comprobacion->id,
        'solicitud'    => $solicitud->id,
        'documento'    => DocumentoSolicitud::first()->id,
        'mensaje'      => $mensaje->id,
        'role'         => Role::findByName('Consulta')->id,
        // Un usuario sin desembolsos ni otros registros: al registrador no se le puede borrar por la llave foránea
        'user'         => operativoConRol(null)->id,
        'fallo'        => 1,
        // Por ruta: el registro en el estado que esa pantalla exige
        'por_ruta'     => [
            'creditos.dictamen'             => ['credito' => $liquidado->id],
            'creditos.dictamen.pdf'         => ['credito' => $liquidado->id],
            'creditos.constancia.pdf'       => ['credito' => $liquidado->id],
            // El crédito base ya tiene desembolso y la pantalla respondería 422; este no tiene
            'creditos.desembolso.create'    => ['credito' => $liquidado->id],
            'creditos.desembolso.store'     => ['credito' => $liquidado->id],
            'solicitudes.registrar-credito' => ['solicitud' => $aprobada->id],
            'solicitudes.guardar-credito'   => ['solicitud' => $aprobada->id],
            'solicitudes.analisis.create'   => ['solicitud' => $enRevision->id],
            'solicitudes.analisis.store'    => ['solicitud' => $enRevision->id],
        ],
    ];
}

/** Hace la petición como el usuario indicado y devuelve el código de respuesta. */
function codigoDeRespuesta(User $usuario, string $metodo, string $nombre, array $parametros): int
{
    $parametros = ($parametros['por_ruta'][$nombre] ?? []) + $parametros;
    $ruta = app('router')->getRoutes()->getByName($nombre);
    $url  = route($nombre, array_intersect_key($parametros, array_flip($ruta->parameterNames())));

    test()->actingAs($usuario);

    return test()->withoutVite()->call($metodo, $url)->getStatusCode();
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/*
| Rutas de administración: hoy exigen role:Administrador y deben seguir exigiéndolo.
| Si alguna deja de exigirlo, esta prueba falla.
*/
function rutasDeAdministracion(): array
{
    return [
        'GET admin/tareas-programadas'                     => ['GET', 'bitacora-tareas.index'],
        'GET admin/correos-fallidos'                       => ['GET', 'correos-fallidos.index'],
        'POST admin/correos-fallidos/reintentar-todos'     => ['POST', 'correos-fallidos.reintentar-todos'],
        'POST admin/correos-fallidos/{fallo}/reintentar'   => ['POST', 'correos-fallidos.reintentar'],
        'POST admin/correos-fallidos/{fallo}/descartar'    => ['POST', 'correos-fallidos.descartar'],
        'GET admin/recordatorios'                          => ['GET', 'recordatorios.index'],
        'POST admin/recordatorios'                         => ['POST', 'recordatorios.enviar'],
        'GET admin/mensajes-ciudadanos'                    => ['GET', 'mensajes-ciudadanos.index'],
        'GET admin/mensajes-ciudadanos/{mensaje}'          => ['GET', 'mensajes-ciudadanos.show'],
        'POST admin/mensajes-ciudadanos/{mensaje}/atendido' => ['POST', 'mensajes-ciudadanos.atendido'],
        'GET admin/importar-acreditados'                   => ['GET', 'importar-acreditados.index'],
        'GET admin/importar-acreditados/plantilla'         => ['GET', 'importar-acreditados.plantilla'],
        'POST admin/importar-acreditados/previsualizar'    => ['POST', 'importar-acreditados.previsualizar'],
        'POST admin/importar-acreditados/confirmar'        => ['POST', 'importar-acreditados.confirmar'],
        'POST admin/importar-acreditados/cancelar'         => ['POST', 'importar-acreditados.cancelar'],
        'POST admin/notificaciones-tareas/leidas'          => ['POST', 'notificaciones-tareas.leidas'],
        'GET admin/respaldo-excel'                         => ['GET', 'respaldo-excel.descargar'],
        'GET auditoria'                                    => ['GET', 'auditoria.index'],
    ];
}

test('un operativo sin rol o con otro rol recibe 403 y el administrador entra', function (string $metodo, string $nombre) {
    $parametros = parametrosDeRutas();

    expect(codigoDeRespuesta(operativoConRol(null), $metodo, $nombre, $parametros))->toBe(403)
        ->and(codigoDeRespuesta(operativoConRol('Cobranza'), $metodo, $nombre, $parametros))->toBe(403)
        ->and(codigoDeRespuesta(operativoConRol('Administrador'), $metodo, $nombre, $parametros))->not->toBe(403);
})->with(rutasDeAdministracion());

test('todas las rutas con role:Administrador del grupo operativo están en esta prueba', function () {
    $conRol = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($ruta) => in_array('operativo', $ruta->gatherMiddleware()) && in_array('role:Administrador', $ruta->gatherMiddleware()))
        ->map(fn ($ruta) => $ruta->getName())
        ->sort()->values()->all();

    $enLaPrueba = collect(rutasDeAdministracion())->pluck(1)->sort()->values()->all();

    expect($conRol)->toBe($enLaPrueba);
});

/*
| Rutas de módulos de otras personas (pagos, créditos, condonaciones, usuarios...). No se corrigen en la J.16:
| se reportan en el ticket. El permiso que deberían exigir sale de RoleAndPermissionSeeder; 'role:Administrador'
| donde el seeder no define un permiso para esa acción.
| Si hoy dejan pasar a quien no debe, la prueba queda como incompleta con el código que respondió (evidencia
| del hallazgo). Cuando el dueño del módulo las proteja, pasan solas y conviene moverlas al dataset de arriba.
*/
function rutasDeOtrosModulos(): array
{
    return [
        'GET dashboard'                                 => ['GET', 'dashboard', 'dashboard.ver'],
        'GET acreditados'                               => ['GET', 'acreditados.index', 'acreditados.ver'],
        'GET acreditados/create'                        => ['GET', 'acreditados.create', 'acreditados.gestionar'],
        'POST acreditados'                              => ['POST', 'acreditados.store', 'acreditados.gestionar'],
        'GET acreditados/{acreditado}'                  => ['GET', 'acreditados.show', 'acreditados.ver'],
        'GET acreditados/{acreditado}/edit'             => ['GET', 'acreditados.edit', 'acreditados.gestionar'],
        'PUT acreditados/{acreditado}'                  => ['PUT', 'acreditados.update', 'acreditados.gestionar'],
        'DELETE acreditados/{acreditado}'               => ['DELETE', 'acreditados.destroy', 'acreditados.gestionar'],
        'GET acreditados/{acreditado}/expediente'       => ['GET', 'acreditados.expediente', 'acreditados.ver'],
        'GET acreditados/{acreditado}/operaciones'      => ['GET', 'operaciones.index', 'acreditados.ver'],
        'GET api/notificaciones/cuotas'                 => ['GET', 'notificaciones.cuotas', 'cobranza.ver'],
        'GET cobranza'                                  => ['GET', 'cobranza.index', 'cobranza.ver'],
        'GET cobranza/{credito}'                        => ['GET', 'cobranza.show', 'cobranza.ver'],
        'POST cobranza/{credito}/gestion'               => ['POST', 'cobranza.gestion', 'cobranza.gestionar'],
        'POST cobranza/{credito}/juridico'              => ['POST', 'cobranza.juridico', 'cobranza.gestionar'],
        'GET comprobaciones'                            => ['GET', 'comprobaciones.index', 'acreditados.ver'],
        'GET comprobaciones/{comprobacion}'             => ['GET', 'comprobaciones.show', 'acreditados.ver'],
        'POST comprobaciones/{comprobacion}/aprobar'    => ['POST', 'comprobaciones.aprobar', 'acreditados.gestionar'],
        'POST comprobaciones/{comprobacion}/rechazar'   => ['POST', 'comprobaciones.rechazar', 'acreditados.gestionar'],
        'GET creditos/{credito}/condonacion-formal'     => ['GET', 'creditos.condonacion-formal.create', 'role:Administrador'],
        'POST creditos/{credito}/condonacion-formal'    => ['POST', 'creditos.condonacion-formal.store', 'role:Administrador'],
        'POST creditos/{credito}/condonar'              => ['POST', 'creditos.condonar', 'role:Administrador'],
        'GET creditos/{credito}/reestructurar'          => ['GET', 'creditos.reestructurar.create', 'role:Administrador'],
        'POST creditos/{credito}/reestructurar'         => ['POST', 'creditos.reestructurar.store', 'role:Administrador'],
        'GET creditos/{credito}/dictamen'               => ['GET', 'creditos.dictamen', 'acreditados.ver'],
        'GET creditos/{credito}/dictamen/pdf'           => ['GET', 'creditos.dictamen.pdf', 'acreditados.ver'],
        'GET creditos/{credito}/contrato/pdf'           => ['GET', 'creditos.contrato.pdf', 'acreditados.ver'],
        'GET creditos/{credito}/estado-cuenta/pdf'      => ['GET', 'creditos.estado-cuenta.pdf', 'acreditados.ver'],
        'GET creditos/{credito}/constancia/pdf'         => ['GET', 'creditos.constancia.pdf', 'acreditados.ver'],
        'GET creditos/{credito}/desembolso'             => ['GET', 'creditos.desembolso.create', 'desembolsos.gestionar'],
        'POST creditos/{credito}/desembolso'            => ['POST', 'creditos.desembolso.store', 'desembolsos.gestionar'],
        'GET desembolso/{desembolso}/comprobante'       => ['GET', 'desembolso.comprobante', 'desembolsos.ver'],
        'GET creditos/{credito}/pagos/create'           => ['GET', 'pagos.create', 'pagos.registrar'],
        'POST creditos/{credito}/pagos'                 => ['POST', 'pagos.store', 'pagos.registrar'],
        'POST pagos/{pago}/cancelar'                    => ['POST', 'pagos.cancelar', 'pagos.cancelar'],
        'GET pagos/{pago}/recibo'                       => ['GET', 'pagos.recibo', 'pagos.ver'],
        'GET pagos/{pago}/recibo/pdf'                   => ['GET', 'pagos.recibo.pdf', 'pagos.ver'],
        'GET exportar/cartera'                          => ['GET', 'exportar.cartera', 'reportes.ver'],
        'GET exportar/pagos'                            => ['GET', 'exportar.pagos', 'reportes.ver'],
        'GET exportar/movimientos/{acreditado}'         => ['GET', 'operaciones.export', 'reportes.ver'],
        'GET reportes/cartera'                          => ['GET', 'reportes.cartera', 'reportes.ver'],
        'GET reportes/pagos'                            => ['GET', 'reportes.pagos', 'reportes.ver'],
        'GET reportes/adeudo/{credito}'                 => ['GET', 'reportes.adeudo', 'reportes.ver'],
        'GET reportes/beneficiarios'                    => ['GET', 'reportes.beneficiarios', 'reportes.ver'],
        'GET reportes/informe-cobranza'                 => ['GET', 'reportes.informe-cobranza', 'reportes.ver'],
        'GET simulador'                                 => ['GET', 'simulador.index', 'simulador.ver'],
        'GET roles'                                     => ['GET', 'roles.index', 'roles.ver'],
        'POST roles'                                    => ['POST', 'roles.store', 'roles.gestionar'],
        'PUT roles/{role}'                              => ['PUT', 'roles.update', 'roles.gestionar'],
        'DELETE roles/{role}'                           => ['DELETE', 'roles.destroy', 'roles.gestionar'],
        'GET users'                                     => ['GET', 'users.index', 'usuarios.ver'],
        'POST users'                                    => ['POST', 'users.store', 'usuarios.gestionar'],
        'PUT users/{user}'                              => ['PUT', 'users.update', 'usuarios.gestionar'],
        'DELETE users/{user}'                           => ['DELETE', 'users.destroy', 'usuarios.gestionar'],
        'GET solicitudes'                               => ['GET', 'solicitudes.index', 'solicitudes.ver'],
        'GET solicitudes/{solicitud}'                   => ['GET', 'solicitudes.show', 'solicitudes.ver'],
        'POST solicitudes/{solicitud}/cambiar-estatus'  => ['POST', 'solicitudes.cambiar-estatus', 'solicitudes.gestionar'],
        'POST solicitudes/{solicitud}/asignar'          => ['POST', 'solicitudes.asignar', 'solicitudes.gestionar'],
        'POST solicitudes/documento/{documento}/revisar' => ['POST', 'solicitudes.revisar-documento', 'solicitudes.gestionar'],
        'POST solicitudes/{solicitud}/anuncio'          => ['POST', 'solicitudes.enviar-anuncio', 'solicitudes.gestionar'],
        'GET solicitudes/{solicitud}/registrar-credito' => ['GET', 'solicitudes.registrar-credito', 'solicitudes.aprobar'],
        'POST solicitudes/{solicitud}/registrar-credito' => ['POST', 'solicitudes.guardar-credito', 'solicitudes.aprobar'],
        'GET solicitudes/{solicitud}/analisis'          => ['GET', 'solicitudes.analisis.create', 'analisis.gestionar'],
        'POST solicitudes/{solicitud}/analisis'         => ['POST', 'solicitudes.analisis.store', 'analisis.gestionar'],
    ];
}

test('hallazgo J.16: un operativo sin rol, o con un rol sin permiso, no debería entrar', function (string $metodo, string $nombre, string $exige) {
    $parametros = parametrosDeRutas();

    // Cobranza solo debe entrar si su rol tiene el permiso en RoleAndPermissionSeeder
    $cobranzaPuede = ! str_starts_with($exige, 'role:') && Role::findByName('Cobranza')->hasPermissionTo($exige);

    // Si el administrador también recibiera 403, el 403 de los demás no diría nada de permisos
    expect(codigoDeRespuesta(operativoConRol('Administrador'), $metodo, $nombre, $parametros))->not->toBe(403);

    $sinRol   = codigoDeRespuesta(operativoConRol(null), $metodo, $nombre, $parametros);
    $cobranza = codigoDeRespuesta(operativoConRol('Cobranza'), $metodo, $nombre, $parametros);

    $huecos = array_filter([
        $sinRol !== 403 ? "sin rol responde {$sinRol}" : null,
        ! $cobranzaPuede && $cobranza !== 403 ? "Cobranza responde {$cobranza}" : null,
    ]);

    if ($huecos) {
        $this->markTestIncomplete("Debería exigir {$exige}; hoy " . implode(' y ', $huecos) . '. Reportado en el ticket J.16.');
    }

    expect($sinRol)->toBe(403);
})->with(rutasDeOtrosModulos());

test('el inventario cubre todas las rutas del grupo operativo', function () {
    $grupo = collect(app('router')->getRoutes()->getRoutes())
        ->filter(fn ($ruta) => in_array('operativo', $ruta->gatherMiddleware()))
        ->map(fn ($ruta) => $ruta->getName())
        ->unique()->sort()->values()->all();

    $enLaPrueba = collect(rutasDeAdministracion())->pluck(1)
        ->merge(collect(rutasDeOtrosModulos())->pluck(1))
        ->unique()->sort()->values()->all();

    expect($enLaPrueba)->toBe($grupo);
});

/*
| Descargas y archivos subidos (puntos 4 y 6 de la J.16)
*/

test('cada descarga del respaldo Excel queda en auditoria_log con el usuario y no deja archivos en el servidor', function () {
    Storage::fake('local');
    Storage::fake('public');
    $admin = operativoConRol('Administrador');

    // Un intento rechazado no se anota
    $this->actingAs(operativoConRol('Cobranza'))->get(route('respaldo-excel.descargar'))->assertForbidden();
    expect(AuditoriaLog::count())->toBe(0);

    $respuesta = $this->actingAs($admin)->get(route('respaldo-excel.descargar'))->assertOk();
    $respuesta->streamedContent();

    $registro = AuditoriaLog::sole();

    expect($registro->only(['entidad', 'accion', 'campo_modificado', 'usuario_id', 'usuario_nombre', 'descripcion']))->toBe([
        'entidad'          => 'RespaldoExcel',
        'accion'           => 'updated',
        'campo_modificado' => 'respaldo_descargado',
        'usuario_id'       => $admin->id,
        'usuario_nombre'   => $admin->name,
        'descripcion'      => 'Respaldo Excel de créditos descargado',
    ])
        // Queda el nombre del archivo que se llevó
        ->and($registro->valor_nuevo)->toMatch('/^Respaldo_creditos_\d{8}_\d{6}\.xlsx$/')
        ->and($respuesta->headers->get('content-disposition'))->toContain($registro->valor_nuevo)
        // Se arma en memoria y se manda directo: no queda en ninguna carpeta
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('el importador rechaza lo que no es Excel o pesa más de 2 MB y no guarda el archivo', function (Closure $archivo, string $mensaje) {
    Storage::fake('local');
    Storage::fake('public');

    $this->actingAs(operativoConRol('Administrador'))
        ->post(route('importar-acreditados.previsualizar'), ['archivo' => $archivo()])
        ->assertSessionHasErrors(['archivo' => $mensaje]);

    expect(session('importacion_acreditados'))->toBeNull()
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'un CSV'                         => [fn () => UploadedFile::fake()->createWithContent('acreditados.csv', "nombre_completo,municipio,sexo\nAna,Mérida,M\n"), 'El archivo debe ser Excel (.xlsx o .xls).'],
    // Archivo real: los de UploadedFile::fake() dicen su tipo por la extensión; uno real se revisa por el contenido
    'texto con extensión .xlsx'      => [function () {
        $ruta = tempnam(sys_get_temp_dir(), 'falso');
        file_put_contents($ruta, 'esto no es un Excel');

        return new UploadedFile($ruta, 'acreditados.xlsx', null, null, true);
    }, 'El archivo debe ser Excel (.xlsx o .xls).'],
    'un Excel de más de 2 MB'        => [fn () => UploadedFile::fake()->create('acreditados.xlsx', 2049, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'), 'El archivo no puede pesar más de 2 MB.'],
]);
