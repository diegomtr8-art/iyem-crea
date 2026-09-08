<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Models\Acreditado;
use App\Models\AnuncioCiudadano;
use App\Models\AvalSolicitud;
use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Services\FormatosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WizardSolicitudController extends Controller
{
    public const MUNICIPIOS_YUCATAN = [
        'Abalá','Acanceh','Akil','Baca','Bokobá','Buctzotz','Cacalchén','Calotmul',
        'Cansahcab','Cantamayec','Celestún','Cenotillo','Chacsinkín','Chankom',
        'Chapab','Chemax','Chichimilá','Chicxulub Pueblo','Chikindzonot','Chocholá',
        'Chumayel','Conkal','Cuncunul','Cuzamá','Dzán','Dzemul','Dzidzantún',
        'Dzilam de Bravo','Dzilam González','Dzitás','Dzoncauich','Espita',
        'Halachó','Hocabá','Hoctún','Homún','Huhí','Hunucmá','Ixil','Izamal',
        'Kanasín','Kantunil','Kaua','Kinchil','Kopomá','Mama','Maní','Maxcanú',
        'Mayapán','Mérida','Mocochá','Motul','Muna','Muxupip','Opichén','Oxkutzcab',
        'Panabá','Peto','Progreso','Quintana Roo','Río Lagartos','Sacalum','Samahil',
        'San Felipe','Sanahcat','Santa Elena','Seyé','Sinanché','Sotuta','Sucilá',
        'Sudzal','Suma','Tahdziú','Tahmek','Teabo','Tecoh','Tekal de Venegas',
        'Tekantó','Tekax','Tekit','Tekom','Telchac Pueblo','Telchac Puerto','Temax',
        'Temozón','Tepakán','Tetiz','Teya','Ticul','Timucuy','Tinum','Tixcacalcupul',
        'Tixkokob','Tixmehuac','Tixpéhual','Tizimín','Tunkás','Tzucacab','Uayma',
        'Ucú','Umán','Valladolid','Xocchel','Yaxcabá','Yaxkukul','Yobaín',
    ];

    // ─── Página principal del wizard ─────────────────────────────────────────

    public function index(): Response
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with(['modalidad', 'aval', 'documentos'])->first();
        $modalidades = ModalidadCrea::where('activo', true)
            ->orderByRaw("FIELD(nombre, 'Artesanal', 'Emprendedores', 'Sustentable')")
            ->get(['id', 'nombre', 'tasa_interes', 'monto_minimo', 'monto_maximo', 'plazo_min_meses', 'plazo_max_meses', 'documentos_requeridos']);

        // Serializar datos del aval si existe
        $avalDatos = null;
        if ($solicitud?->aval) {
            $avalDatos = $solicitud->aval->toArray();
        }

        // Documentos ya subidos
        $documentosSubidos = null;
        if ($solicitud) {
            $documentosSubidos = $solicitud->documentos->map(fn($d) => [
                'id'              => $d->id,
                'tipo_documento'  => $d->tipo_documento,
                'nombre_original' => $d->nombre_original,
                'estatus'         => $d->estatus,
                'observacion'     => $d->observacion,
            ])->keyBy('tipo_documento');
        }

        // Documentos post-aprobación ya subidos
        $documentosPostAprobacion = null;
        if ($solicitud && $solicitud->estatus === 'Aprobada') {
            $documentosPostAprobacion = $solicitud->documentos()
                ->whereIn('tipo_documento', array_keys(DocumentoSolicitud::tiposPostAprobacion()))
                ->get()
                ->map(fn($d) => [
                    'tipo_documento'  => $d->tipo_documento,
                    'nombre_original' => $d->nombre_original,
                    'estatus'         => $d->estatus,
                ])->keyBy('tipo_documento');
        }

        return Inertia::render('portal/WizardCredito', [
            'solicitud'  => $solicitud ? array_merge($solicitud->toArray(), [
                'documentos' => $documentosSubidos,
                'aval'       => $avalDatos,
            ]) : null,
            'modalidades' => $modalidades,
            'tipos_documentos_post_aprobacion' => DocumentoSolicitud::tiposPostAprobacion(),
            'documentos_post_aprobacion'       => $documentosPostAprobacion,
        ]);
    }

    // ─── Paso 0: Verificar CURP ───────────────────────────────────────────────

    public function verificarCurp(Request $request): JsonResponse
    {
        $request->validate(['curp' => 'required|string|size:18']);
        $curp  = strtoupper(trim($request->curp));
        $userId = auth()->id();

        // Bloquear si otro usuario tiene crédito activo o solicitud en proceso con esa CURP
        $conflicto = SolicitudCredito::where('curp', $curp)
            ->where('user_id', '!=', $userId)
            ->whereIn('estatus', ['Enviada', 'En_Revision', 'Documentacion_Incompleta', 'Aprobada'])
            ->exists();

        if ($conflicto) {
            return response()->json([
                'bloqueado' => true,
                'mensaje'   => 'Ya existe una solicitud activa o crédito asociado a esta CURP. Solo se permite un crédito CREA activo por persona.',
            ]);
        }

        // Renovación: ¿ya tuvo un crédito CREA previo totalmente liquidado?
        $esRenovacion = Acreditado::where('curp', $curp)
            ->whereHas('creditos', fn($q) => $q->where('estatus', 'Liquidado'))
            ->exists();

        // Si el propio usuario ya tiene solicitud con esta CURP, permitir continuar
        return response()->json(['bloqueado' => false, 'renovacion' => $esRenovacion]);
    }

    // ─── Guardar paso del wizard ──────────────────────────────────────────────

    public function guardarPaso(Request $request): JsonResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;

        // Crear solicitud si no existe
        if (!$solicitud) {
            $solicitud = SolicitudCredito::create([
                'user_id' => $user->id,
                'estatus' => 'Borrador',
            ]);
        }

        // Solo se puede editar en estos estados
        if (!in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return response()->json(['error' => 'No puedes modificar esta solicitud.'], 403);
        }

        $paso = $request->input('paso');
        $datos = $request->input('datos', []);
        // 'envio' (default): validación estricta antes de generar los formatos.
        // 'borrador': solo se validan formatos/tipos, no la obligatoriedad, para
        // permitir guardar el progreso con campos aún incompletos.
        $modo = $request->input('modo', 'borrador');
        // Guard: si modo llega como objeto (bug click event Vue), normalizar a 'borrador'
        if (!is_string($modo) || !in_array($modo, ['borrador', 'envio'])) {
            $modo = 'borrador';
        }

        // Actualizar wizard_datos en función del paso
        $wizard = $solicitud->datos_wizard ?? [];
        $wizard['paso_completado'] = max($wizard['paso_completado'] ?? 0, $paso);

        file_put_contents(storage_path('logs/debug_aval.txt'),
            date('Y-m-d H:i:s') . " PASO=$paso MODO=$modo TIPO_GARANTIA=" . ($solicitud->tipo_garantia ?? 'NULL') . "\n",
            FILE_APPEND
        );

        switch ($paso) {
            case 1: // Tipo crédito, persona, garantía
                $request->validate([
                    'datos.modalidad_id'  => 'required|exists:modalidad_creas,id',
                    'datos.tipo_persona'  => 'required|in:fisica,moral',
                    'datos.tipo_garantia' => 'required|in:aval,prendaria,hipotecaria',
                ]);
                $solicitud->update([
                    'modalidad_id' => $datos['modalidad_id'],
                    'tipo_persona' => $datos['tipo_persona'],
                    'tipo_garantia' => $datos['tipo_garantia'],
                ]);
                break;

            case 2: // Datos personales básicos + extendidos
                $request->validate($this->suavizarReglas([
                    'datos.nombre_completo'  => 'required|string|max:255',
                    'datos.curp'             => ['required', 'string', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM](AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/'],
                    'datos.fecha_nacimiento' => 'required|date|before:' . now()->subYears(18)->format('Y-m-d'),
                    'datos.sexo'             => 'required|in:M,F',
                    'datos.municipio'        => ['required', 'string', Rule::in(self::MUNICIPIOS_YUCATAN)],
                    'datos.direccion'        => 'required|string|max:1000',
                    'datos.telefono'         => 'required|digits:10',
                    'datos.correo'           => 'required|email:rfc|max:255',
                ], $modo), [
                    'datos.fecha_nacimiento.before' => 'Debes ser mayor de edad (18 años) para solicitar un crédito.',
                    'datos.telefono.digits'         => 'El teléfono celular debe tener 10 dígitos.',
                ]);
                $solicitud->update([
                    'nombre_completo' => $datos['nombre_completo'],
                    'curp'            => strtoupper(trim($datos['curp'])),
                    'rfc'             => $datos['rfc'] ?? null,
                    'fecha_nacimiento'=> $datos['fecha_nacimiento'] ?: null,
                    'sexo'            => $datos['sexo'] ?: null,
                    'mayahablante'    => (bool)($datos['mayahablante'] ?? false),
                    'discapacidad'    => (bool)($datos['discapacidad'] ?? false),
                    'municipio'       => $datos['municipio'],
                    'direccion'       => $datos['direccion'],
                    'telefono'        => $datos['telefono'],
                    'correo'          => $datos['correo'],
                ]);
                // Guardar campos extendidos en datos_wizard
                $wizard['datos_personales_ext'] = [
                    'colonia'             => $datos['colonia'] ?? '',
                    'cp'                  => $datos['cp'] ?? '',
                    'lugar_nacimiento'    => $datos['lugar_nacimiento'] ?? '',
                    'telefono_fijo'       => $datos['telefono_fijo'] ?? '',
                    'domicilio_propio'    => $datos['domicilio_propio'] ?? true,
                    'renta_mensual'       => $datos['renta_mensual'] ?? null,
                    'empleado_gobierno'   => $datos['empleado_gobierno'] ?? false,
                    'dependencia_gobierno'=> $datos['dependencia_gobierno'] ?? '',
                    'puesto_gobierno'     => $datos['puesto_gobierno'] ?? '',
                    'estado_civil'        => $datos['estado_civil'] ?? '',
                    'regimen_matrimonial' => $datos['regimen_matrimonial'] ?? '',
                    'nombre_conyuge'      => $datos['nombre_conyuge'] ?? '',
                    'curp_conyuge'        => $datos['curp_conyuge'] ?? '',
                    'discapacidad_tipo'   => $datos['discapacidad_tipo'] ?? '',
                    'referencia_nombre'   => $datos['referencia_nombre'] ?? '',
                    'referencia_telefono' => $datos['referencia_telefono'] ?? '',
                    'referencia_cp'       => $datos['referencia_cp'] ?? '',
                ];

                if (!empty($datos['rfc']) && strlen($datos['rfc']) >= 4 && strlen($datos['curp']) >= 4) {
                    if (strtoupper(substr($datos['rfc'], 0, 4)) !== strtoupper(substr($datos['curp'], 0, 4))) {
                        \Log::warning("CURP/RFC mismatch: solicitud={$solicitud->id} RFC={$datos['rfc']} CURP={$datos['curp']}");
                    }
                }

                // Renovación: beneficiario con un crédito CREA previo ya liquidado
                $wizard['es_renovacion'] = Acreditado::where('curp', strtoupper(trim($datos['curp'])))
                    ->whereHas('creditos', fn($q) => $q->where('estatus', 'Liquidado'))
                    ->exists();
                break;

            case 3: // Persona moral (condicional)
                if ($solicitud->tipo_persona === 'moral') {
                    $wizard['datos_persona_moral'] = [
                        'razon_social'   => $datos['razon_social'] ?? '',
                        'rfc'            => $datos['rfc_moral'] ?? '',
                        'domicilio'      => $datos['domicilio_moral'] ?? '',
                        'colonia'        => $datos['colonia_moral'] ?? '',
                        'municipio'      => $datos['municipio_moral'] ?? '',
                        'cp'             => $datos['cp_moral'] ?? '',
                        'telefono'       => $datos['telefono_moral'] ?? '',
                        'correo'         => $datos['correo_moral'] ?? '',
                        'rep_legal'      => $datos['rep_legal'] ?? '',
                        'curp_rep'       => $datos['curp_rep'] ?? '',
                        'fecha_constitucion' => $datos['fecha_constitucion'] ?? '',
                    ];
                }
                break;

            case 4: // Datos del negocio extendidos
                $request->validate($this->suavizarReglas([
                    'datos.nombre_comercial' => 'required|string|max:255',
                    'datos.giro_comercial'   => 'required|string|max:255',
                ], $modo));

                $modalidadActual = ModalidadCrea::find($solicitud->modalidad_id);
                if (!empty($datos['giro_comercial'])) {
                    $giroProhibido = $this->girosProhibidos($modalidadActual?->nombre, $datos['giro_comercial']);
                    if ($giroProhibido) {
                        throw ValidationException::withMessages([
                            'datos.giro_comercial' => "El giro del negocio no es elegible para esta modalidad CREA ({$giroProhibido}).",
                        ]);
                    }
                }

                if ($modalidadActual && str_contains(strtolower($modalidadActual->nombre), 'sustentable')) {
                    $request->validate($this->suavizarReglas([
                        'datos.antiguedad_sat_anios' => 'required|integer|min:1',
                    ], $modo));
                }

                $solicitud->update([
                    'giro_comercial'     => $datos['giro_comercial'],
                    'descripcion_negocio'=> $datos['descripcion_negocio'] ?? '',
                    'alta_sat'           => (bool)($datos['alta_sat'] ?? false),
                    'es_emprendimiento'  => (bool)($datos['es_emprendimiento'] ?? false),
                ]);
                $wizard['datos_negocio_ext'] = [
                    'nombre_comercial'      => $datos['nombre_comercial'] ?? '',
                    'municipio_empresa'     => $datos['municipio_empresa'] ?? '',
                    'fecha_inicio_negocio'  => $datos['fecha_inicio_negocio'] ?? '',
                    'regimen_fiscal'        => $datos['regimen_fiscal'] ?? '',
                    'propiedad_intelectual' => $datos['propiedad_intelectual'] ?? false,
                    'detalle_pi'            => $datos['detalle_pi'] ?? '',
                    'ventas_mensuales'      => $datos['ventas_mensuales'] ?? null,
                    'proceso_operacion'     => $datos['proceso_operacion'] ?? '',
                    'mobiliario'            => $datos['mobiliario'] ?? '',
                    'recursos_humanos'      => $datos['recursos_humanos'] ?? '',
                    'es_emprendimiento'     => (bool)($datos['es_emprendimiento'] ?? false),
                    'antiguedad_sat_anios'  => $datos['antiguedad_sat_anios'] ?? null,
                ];
                break;

            case 5: // Destino del crédito + apoyos gobierno
                $modalidad = ModalidadCrea::find($solicitud->modalidad_id);
                $montoMin = $modalidad ? (float)$modalidad->monto_minimo : 100;
                $montoMax = $modalidad ? (float)$modalidad->monto_maximo : 1000000;
                $plazosValidos = $this->plazosPermitidos($modalidad?->nombre);

                $request->validate($this->suavizarReglas([
                    'datos.monto_solicitado' => "required|numeric|min:{$montoMin}|max:{$montoMax}",
                    'datos.plazo_meses'      => 'required|in:' . implode(',', $plazosValidos),
                ], $modo), [
                    'datos.monto_solicitado.min' => 'El monto mínimo para ' . ($modalidad?->nombre ?? 'esta modalidad') . ' es ' . number_format($montoMin, 0, '.', ',') . ' MXN.',
                    'datos.monto_solicitado.max' => 'El monto máximo para ' . ($modalidad?->nombre ?? 'esta modalidad') . ' es ' . number_format($montoMax, 0, '.', ',') . ' MXN.',
                ]);

                $solicitud->update([
                    'monto_solicitado' => $datos['monto_solicitado'] !== '' && $datos['monto_solicitado'] !== null ? $datos['monto_solicitado'] : null,
                    'plazo_meses'      => $datos['plazo_meses'] !== '' && $datos['plazo_meses'] !== null ? (int)$datos['plazo_meses'] : null,
                    'destino_credito'  => $datos['destino_credito'] ?? '',
                ]);

                $wizard['destino_credito_tabla'] = $datos['destino_credito_tabla'] ?? [];
                $wizard['apoyos_gobierno']       = $datos['apoyos_gobierno'] ?? [];
                $wizard['importe_total_proyecto'] = $datos['importe_total_proyecto'] ?? null;
                break;

            case 6: // Ficha técnica (proveedores, clientes, deudas)
                $wizard['proveedores']    = $datos['proveedores'] ?? [];
                $wizard['clientes']       = $datos['clientes'] ?? [];
                $wizard['deudas_negocio'] = $datos['deudas_negocio'] ?? [];
                $wizard['productos']      = $datos['productos'] ?? '';
                $wizard['distribucion']   = $datos['distribucion'] ?? '';
                break;

            case 7: // Ingresos y egresos
                $camposIe = fn(array $d) => [
                    'periodo_del'        => $d['periodo_del'] ?? '',
                    'periodo_al'         => $d['periodo_al'] ?? '',
                    'ventas'             => $d['ventas'] ?? null,
                    'costo_producto'     => $d['costo_producto'] ?? null,
                    'gastos_electricidad'=> $d['gastos_electricidad'] ?? null,
                    'gastos_agua'        => $d['gastos_agua'] ?? null,
                    'gastos_telefono'    => $d['gastos_telefono'] ?? null,
                    'gastos_gas'         => $d['gastos_gas'] ?? null,
                    'gastos_mano_obra'   => $d['gastos_mano_obra'] ?? null,
                    'gastos_nomina'      => $d['gastos_nomina'] ?? null,
                    'gastos_renta_local' => $d['gastos_renta_local'] ?? null,
                    'otros_gastos'       => $d['otros_gastos'] ?? [],
                    'impuestos'          => $d['impuestos'] ?? null,
                ];
                // Proyección siempre presente
                if (!empty($datos['proyeccion'])) {
                    $wizard['proyeccion_ingresos_egresos'] = $camposIe($datos['proyeccion']);
                }
                // Historial solo para negocios en operación (no emprendimiento)
                if (!empty($datos['historico'])) {
                    $wizard['ingresos_egresos'] = $camposIe($datos['historico']);
                }
                // Compatibilidad hacia atrás: si vienen campos planos, guardarlos también
                if (empty($datos['proyeccion']) && empty($datos['historico'])) {
                    $wizard['ingresos_egresos'] = $camposIe($datos);
                }
                break;

            case 8: // Aval o garantía
                if ($solicitud->tipo_garantia === 'aval') {
                    $request->validate($this->suavizarReglas([
                        'datos.nombre_completo'  => 'required|string|max:150',
                        'datos.curp'             => ['required', 'size:18', 'regex:/^[A-Z]{4}\d{6}[HM](AS|BC|BS|CC|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/'],
                        'datos.telefono_celular' => 'required|digits:10',
                        'datos.correo'           => 'required|email:rfc',
                        'datos.fecha_nacimiento' => 'required|date|before:' . now()->subYears(18)->format('Y-m-d'),
                    ], $modo), [
                        'datos.nombre_completo.required'  => 'El nombre completo del aval es obligatorio.',
                        'datos.curp.required'             => 'La CURP del aval es obligatoria.',
                        'datos.curp.regex'                => 'La CURP del aval no tiene un formato válido.',
                        'datos.telefono_celular.required' => 'El teléfono celular del aval es obligatorio.',
                        'datos.telefono_celular.digits'   => 'El teléfono celular del aval debe tener 10 dígitos.',
                        'datos.correo.required'           => 'El correo del aval es obligatorio.',
                        'datos.correo.email'              => 'El correo del aval no es válido.',
                        'datos.fecha_nacimiento.required' => 'La fecha de nacimiento del aval es obligatoria.',
                        'datos.fecha_nacimiento.before'   => 'El aval debe ser mayor de edad.',
                    ]);

                    $modalidadActual = ModalidadCrea::find($solicitud->modalidad_id);
                    if ($modalidadActual && str_contains(strtolower($modalidadActual->nombre), 'artesanal')) {
                        $parentescosProhibidos = [
                            'padre', 'madre', 'hijo', 'hija', 'hermano', 'hermana',
                            'abuelo', 'abuela', 'nieto', 'nieta', 'tío', 'tía',
                            'tio', 'tia', 'sobrino', 'sobrina',
                        ];
                        $parentesco = strtolower(trim($datos['parentesco'] ?? ''));
                        if (in_array($parentesco, $parentescosProhibidos, true)) {
                            throw ValidationException::withMessages([
                                'datos.parentesco' => 'El aval no puede ser familiar hasta 2do grado de consanguinidad del solicitante en la modalidad Artesanal.',
                            ]);
                        }
                    }
                    try {
                        $this->guardarAval($solicitud, $datos);
                    } catch (\Throwable $e) {
                        file_put_contents(storage_path('logs/debug_aval.txt'),
                            date('Y-m-d H:i:s') . "\n" .
                            $e->getMessage() . "\n" .
                            $e->getTraceAsString() . "\n\n",
                            FILE_APPEND
                        );
                        throw $e;
                    }
                } else {
                    $wizard['garantia_datos'] = [
                        'descripcion'   => $datos['descripcion'] ?? '',
                        'valor'         => $datos['valor'] ?? null,
                        'fecha_factura' => $datos['fecha_factura'] ?? null,
                        'valor_factura' => $datos['valor_factura'] ?? null,
                    ];
                }
                break;
        }

        $solicitud->update(['datos_wizard' => $wizard]);

        return response()->json([
            'ok'          => true,
            'solicitud_id'=> $solicitud->id,
        ]);
    }

    // ─── Subir documento (reutiliza lógica, disco local) ─────────────────────

    public function subirDocumento(Request $request): JsonResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;

        if (!$solicitud || !in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return response()->json(['error' => 'No puedes subir documentos en este momento.'], 403);
        }

        $tiposPermitidos = array_keys($this->tiposRequeridosSolicitud($solicitud));

        $request->validate([
            'tipo_documento' => 'required|in:' . implode(',', $tiposPermitidos),
            'archivo'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'archivo.max'   => 'El archivo no debe pesar más de 10 MB.',
            'archivo.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
        ]);

        $archivo   = $request->file('archivo');
        $tipo      = $request->tipo_documento;
        $extension = $archivo->getClientOriginalExtension();

        // Guardar en disco local (privado), NO en public
        $ruta = $archivo->storeAs(
            "solicitudes/{$solicitud->id}",
            "{$tipo}.{$extension}",
            'local'
        );

        $solicitud->documentos()->where('tipo_documento', $tipo)->delete();

        $doc = DocumentoSolicitud::create([
            'solicitud_id'    => $solicitud->id,
            'tipo_documento'  => $tipo,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta_archivo'    => $ruta,
            'estatus'         => 'Pendiente',
        ]);

        return response()->json([
            'ok'             => true,
            'tipo_documento' => $tipo,
            'nombre_original'=> $doc->nombre_original,
            'estatus'        => $doc->estatus,
        ]);
    }

    // ─── Enviar solicitud (cambia estatus a Enviada y genera el ZIP de formatos) ──

    public function generarFormatos(Request $request): JsonResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with(['modalidad', 'aval', 'documentos'])->first();

        if (!$solicitud || !in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return response()->json(['error' => 'No puedes enviar la solicitud en este momento.'], 403);
        }

        // Verificar documentos completos
        $tiposReq    = array_keys($this->tiposRequeridosSolicitud($solicitud));
        $tiposSubidos = $solicitud->documentos()->pluck('tipo_documento')->toArray();
        $faltantes   = array_diff($tiposReq, $tiposSubidos);

        if (!empty($faltantes)) {
            return response()->json([
                'error'    => 'Faltan documentos por subir.',
                'faltantes'=> array_values($faltantes),
            ], 422);
        }

        $solicitud->update(['estatus' => 'Enviada']);

        $zipDisponible = false;
        try {
            $zipRuta = \App\Services\FormatosService::generarZip($solicitud);
            $solicitud->update(['formatos_zip_ruta' => $zipRuta]);
            $zipDisponible = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("No se pudo generar el ZIP de formatos al enviar la solicitud {$solicitud->id}: {$e->getMessage()}");
        }

        AnuncioCiudadano::create([
            'user_id'    => $user->id,
            'titulo'     => 'Tu solicitud fue recibida',
            'mensaje'    => 'Tu solicitud de crédito CREA fue enviada correctamente. El equipo estará revisando tu información.',
            'tipo'       => 'info',
            'url_accion' => route('portal.solicitud.index'),
        ]);

        return response()->json([
            'ok'             => true,
            'solicitud_id'   => $solicitud->id,
            'zip_disponible' => $zipDisponible,
        ]);
    }

    // ─── Descargar ZIP ────────────────────────────────────────────────────────

    public function descargarFormatos(SolicitudCredito $solicitud): \Symfony\Component\HttpFoundation\Response
    {
        abort_if($solicitud->user_id !== auth()->id(), 403);
        abort_if(!$solicitud->formatos_zip_ruta || !Storage::disk('local')->exists($solicitud->formatos_zip_ruta), 404, 'Formatos no disponibles.');

        $nombre = "CREA_Formatos_{$solicitud->id}.zip";
        return Storage::disk('local')->download($solicitud->formatos_zip_ruta, $nombre);
    }

    // ─── Descargar documento (ruta segura para panel operativo) ──────────────

    public function descargarDocumento(\App\Models\DocumentoSolicitud $documento): \Symfony\Component\HttpFoundation\Response
    {
        $user = auth()->user();

        // Acceso: dueño de la solicitud O usuario operativo
        $solicitud = $documento->solicitud;
        if ($solicitud->user_id !== $user->id && !$user->esOperativo()) {
            abort(403);
        }

        abort_if(!Storage::disk('local')->exists($documento->ruta_archivo), 404);

        return Storage::disk('local')->response($documento->ruta_archivo, $documento->nombre_original);
    }

    // ─── Documentos post-aprobación ───────────────────────────────────────────

    public function subirDocumentoPostAprobacion(Request $request): JsonResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;

        if (!$solicitud || $solicitud->estatus !== 'Aprobada') {
            return response()->json(['error' => 'No puedes subir estos documentos en este momento.'], 403);
        }

        $tiposPermitidos = array_keys(DocumentoSolicitud::tiposPostAprobacion());

        $request->validate([
            'tipo_documento' => 'required|in:' . implode(',', $tiposPermitidos),
        ]);

        $tipo = $request->tipo_documento;
        $solicitud->documentos()->where('tipo_documento', $tipo)->delete();

        if ($tipo === 'google_maps_negocio') {
            $request->validate(['url' => 'required|url|max:500']);

            $doc = DocumentoSolicitud::create([
                'solicitud_id'    => $solicitud->id,
                'tipo_documento'  => $tipo,
                'nombre_original' => $request->input('url'),
                'ruta_archivo'    => 'url',
                'estatus'         => 'Pendiente',
            ]);
        } else {
            $request->validate(['archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'], [
                'archivo.max'   => 'El archivo no debe pesar más de 10 MB.',
                'archivo.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
            ]);

            $archivo   = $request->file('archivo');
            $extension = $archivo->getClientOriginalExtension();
            $ruta = $archivo->storeAs("solicitudes/{$solicitud->id}", "{$tipo}.{$extension}", 'local');

            $doc = DocumentoSolicitud::create([
                'solicitud_id'    => $solicitud->id,
                'tipo_documento'  => $tipo,
                'nombre_original' => $archivo->getClientOriginalName(),
                'ruta_archivo'    => $ruta,
                'estatus'         => 'Pendiente',
            ]);
        }

        return response()->json([
            'ok'             => true,
            'tipo_documento' => $tipo,
            'nombre_original'=> $doc->nombre_original,
        ]);
    }

    // ─── Helpers privados ─────────────────────────────────────────────────────

    /**
     * En modo 'borrador' convierte 'required' en 'nullable' para permitir guardar
     * el progreso con campos aún vacíos, conservando el resto de las reglas
     * (formato, tipo, longitud, etc.). En modo 'envio' las reglas no cambian.
     */
    private function suavizarReglas(array $reglas, string $modo): array
    {
        if ($modo === 'envio') {
            return $reglas;
        }

        foreach ($reglas as $campo => $regla) {
            if (is_string($regla)) {
                $reglas[$campo] = rtrim(preg_replace('/^required\|?/', 'nullable|', $regla), '|');
            } elseif (is_array($regla)) {
                $reglas[$campo] = array_map(fn($r) => $r === 'required' ? 'nullable' : $r, $regla);
            }
        }

        return $reglas;
    }

    private function tiposRequeridosSolicitud(SolicitudCredito $solicitud): array
    {
        $wizard          = $solicitud->datos_wizard ?? [];
        $estadoCivil     = $wizard['datos_personales_ext']['estado_civil'] ?? null;
        $estadoCivilAval = $solicitud->aval?->estado_civil;

        return DocumentoSolicitud::tiposRequeridos(
            $solicitud->modalidad_id,
            $solicitud->tipo_persona,
            $solicitud->tipo_garantia,
            $estadoCivil,
            $estadoCivilAval,
            $solicitud->monto_solicitado ? (float) $solicitud->monto_solicitado : null
        );
    }

    /**
     * Devuelve el motivo de rechazo si el giro coincide con la lista prohibida
     * por modalidad (Arts. 9, 17, 25), o null si es elegible.
     */
    private function girosProhibidos(?string $modalidad, string $giro): ?string
    {
        $normalizar = fn(string $s) => strtr(strtolower($s), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);

        $giroNormalizado = $normalizar($giro);
        $nombreMod       = strtolower($modalidad ?? '');

        $prohibidos = ['vehiculos de transporte'];

        if (str_contains($nombreMod, 'emprendedores') || str_contains($nombreMod, 'sustentable')) {
            $prohibidos = array_merge($prohibidos, [
                'casa de empeño', 'casa de empeno', 'catalogo', 'piramide',
                'estacionamiento', 'tianguista', 'casino',
            ]);
        }

        foreach ($prohibidos as $termino) {
            if (str_contains($giroNormalizado, $normalizar($termino))) {
                return $termino;
            }
        }

        return null;
    }

    private function guardarAval(SolicitudCredito $solicitud, array $datos): void
    {
        AvalSolicitud::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'nombre_completo'          => $datos['nombre_completo'] ?? '',
                'parentesco'               => $datos['parentesco'] ?? null,
                'correo'                   => $datos['correo'] ?? null,
                'sexo'                     => $datos['sexo'] ?? null,
                'rfc'                      => $datos['rfc'] ?? null,
                'curp'                     => $datos['curp'] ?? null,
                'telefono_celular'         => $datos['telefono_celular'] ?? null,
                'telefono_fijo'            => $datos['telefono_fijo'] ?? null,
                'edad'                     => $datos['edad'] ?: null,
                'fecha_nacimiento'         => $datos['fecha_nacimiento'] ?: null,
                'municipio_nacimiento'     => $datos['municipio_nacimiento'] ?? null,
                'municipio_residencia'     => $datos['municipio_residencia'] ?? null,
                'domicilio'                => $datos['domicilio'] ?? null,
                'colonia'                  => $datos['colonia'] ?? null,
                'cp'                       => $datos['cp'] ?? null,
                'domicilio_propio'         => (bool)($datos['domicilio_propio'] ?? true),
                'renta_mensual'            => $datos['renta_mensual'] ?: null,
                'lugar_laboral'            => $datos['lugar_laboral'] ?? null,
                'antiguedad_laboral'       => $datos['antiguedad_laboral'] ?? null,
                'ocupacion'                => $datos['ocupacion'] ?? null,
                'fecha_inicio_actividades' => $datos['fecha_inicio_actividades'] ?: null,
                'dependientes_economicos'  => $datos['dependientes_economicos'] ?: null,
                'estado_civil'             => $datos['estado_civil'] ?? null,
                'regimen_matrimonial'      => $datos['regimen_matrimonial'] ?? null,
                'nombre_conyuge'           => $datos['nombre_conyuge'] ?? null,
                'bienes_inmuebles'         => $datos['bienes_inmuebles'] ?? [],
                // bienes_muebles, ingresos, egresos solo si la columna existe (migration 2026_08_18)
                ...(Schema::hasColumn('avales_solicitud', 'bienes_muebles') ? [
                    'bienes_muebles'       => $datos['bienes_muebles'] ?? [],
                    'ingresos'             => $datos['ingresos'] ?? [],
                    'egresos'              => $datos['egresos'] ?? [],
                ] : []),
                'hipotecas_creditos'       => $datos['hipotecas_creditos'] ?? [],
                'otras_deudas'             => $datos['otras_deudas'] ?? [],
                'referencias_personales'   => $datos['referencias_personales'] ?? [],
            ]
        );
    }

    private function plazosPermitidos(?string $modalidad): array
    {
        if (!$modalidad) return [12, 18, 24];
        if (str_contains(strtolower($modalidad), 'artesanal')) return [6, 12, 18];
        return [12, 18, 24];
    }
}
