<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AvalSolicitud;
use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class WizardSolicitudController extends Controller
{
    // ─── Página principal del wizard ─────────────────────────────────────────

    public function index(): Response
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with(['modalidad', 'aval', 'documentos'])->first();
        $modalidades = ModalidadCrea::where('activo', true)->get(['id', 'nombre', 'tasa_interes', 'monto_minimo', 'monto_maximo', 'plazo_min_meses', 'plazo_max_meses', 'documentos_requeridos']);

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

        return Inertia::render('portal/WizardCredito', [
            'solicitud'  => $solicitud ? array_merge($solicitud->toArray(), [
                'documentos' => $documentosSubidos,
                'aval'       => $avalDatos,
            ]) : null,
            'modalidades' => $modalidades,
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

        // Si el propio usuario ya tiene solicitud con esta CURP, permitir continuar
        return response()->json(['bloqueado' => false]);
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

        // Actualizar wizard_datos en función del paso
        $wizard = $solicitud->datos_wizard ?? [];
        $wizard['paso_completado'] = max($wizard['paso_completado'] ?? 0, $paso);

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
                $request->validate([
                    'datos.nombre_completo'  => 'required|string|max:255',
                    'datos.curp'             => 'required|string|size:18',
                    'datos.fecha_nacimiento' => 'required|date|before:today',
                    'datos.sexo'             => 'required|in:M,F',
                    'datos.municipio'        => 'required|string|max:255',
                    'datos.direccion'        => 'required|string|max:1000',
                    'datos.telefono'         => 'required|string|max:20',
                    'datos.correo'           => 'required|email|max:255',
                ]);
                $solicitud->update([
                    'nombre_completo' => $datos['nombre_completo'],
                    'curp'            => strtoupper(trim($datos['curp'])),
                    'rfc'             => $datos['rfc'] ?? null,
                    'fecha_nacimiento'=> $datos['fecha_nacimiento'],
                    'sexo'            => $datos['sexo'],
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
                $request->validate([
                    'datos.nombre_comercial' => 'required|string|max:255',
                    'datos.giro_comercial'   => 'required|string|max:255',
                ]);
                $solicitud->update([
                    'giro_comercial'     => $datos['giro_comercial'],
                    'descripcion_negocio'=> $datos['descripcion_negocio'] ?? '',
                    'alta_sat'           => (bool)($datos['alta_sat'] ?? false),
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
                ];
                break;

            case 5: // Destino del crédito + apoyos gobierno
                $modalidad = ModalidadCrea::find($solicitud->modalidad_id);
                $montoMin = $modalidad ? (float)$modalidad->monto_minimo : 100;
                $montoMax = $modalidad ? (float)$modalidad->monto_maximo : 1000000;
                $plazosValidos = $this->plazosPermitidos($modalidad?->nombre);

                $request->validate([
                    'datos.monto_solicitado' => "required|numeric|min:{$montoMin}|max:{$montoMax}",
                    'datos.plazo_meses'      => 'required|in:' . implode(',', $plazosValidos),
                ]);

                $solicitud->update([
                    'monto_solicitado' => $datos['monto_solicitado'],
                    'plazo_meses'      => (int)$datos['plazo_meses'],
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
                $wizard['ingresos_egresos'] = [
                    'periodo_del'        => $datos['periodo_del'] ?? '',
                    'periodo_al'         => $datos['periodo_al'] ?? '',
                    'ventas'             => $datos['ventas'] ?? null,
                    'costo_producto'     => $datos['costo_producto'] ?? null,
                    'gastos_electricidad'=> $datos['gastos_electricidad'] ?? null,
                    'gastos_agua'        => $datos['gastos_agua'] ?? null,
                    'gastos_telefono'    => $datos['gastos_telefono'] ?? null,
                    'gastos_gas'         => $datos['gastos_gas'] ?? null,
                    'gastos_mano_obra'   => $datos['gastos_mano_obra'] ?? null,
                    'gastos_nomina'      => $datos['gastos_nomina'] ?? null,
                    'gastos_renta_local' => $datos['gastos_renta_local'] ?? null,
                    'otros_gastos'       => $datos['otros_gastos'] ?? [],
                    'impuestos'          => $datos['impuestos'] ?? null,
                ];
                break;

            case 8: // Aval o garantía
                if ($solicitud->tipo_garantia === 'aval') {
                    $this->guardarAval($solicitud, $datos);
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

        $tiposPermitidos = array_keys(DocumentoSolicitud::tiposRequeridos($solicitud->modalidad_id, $solicitud->tipo_persona, $solicitud->tipo_garantia));

        $request->validate([
            'tipo_documento' => 'required|in:' . implode(',', $tiposPermitidos),
            'archivo'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
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

    // ─── Generar PDFs + ZIP ───────────────────────────────────────────────────

    public function generarFormatos(Request $request): JsonResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with(['modalidad', 'aval'])->first();

        if (!$solicitud || !in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return response()->json(['error' => 'No puedes generar formatos ahora.'], 403);
        }

        // Verificar documentos completos
        $tiposReq    = array_keys(DocumentoSolicitud::tiposRequeridos($solicitud->modalidad_id, $solicitud->tipo_persona, $solicitud->tipo_garantia));
        $tiposSubidos = $solicitud->documentos()->pluck('tipo_documento')->toArray();
        $faltantes   = array_diff($tiposReq, $tiposSubidos);

        if (!empty($faltantes)) {
            return response()->json([
                'error'    => 'Faltan documentos por subir.',
                'faltantes'=> array_values($faltantes),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $archivosTemp = [];
            $dompdf       = app('dompdf.wrapper');
            $dompdf->setPaper('letter', 'portrait');

            $modalidadNombre = strtolower($solicitud->modalidad?->nombre ?? 'emprendedores');
            $isArtesanal     = str_contains($modalidadNombre, 'artesanal');
            $tipoPf          = $solicitud->tipo_persona === 'fisica';
            $conAval         = $solicitud->tipo_garantia === 'aval';

            $templateData = [
                'solicitud' => $solicitud,
                'wizard'    => $solicitud->datos_wizard ?? [],
                'aval'      => $solicitud->aval,
                'modalidad' => $solicitud->modalidad,
                'fecha'     => now()->format('d/m/Y'),
            ];

            // Determinar plantilla de Solicitud de Crédito
            if ($isArtesanal) {
                $archivosTemp[] = $this->generarPdf($dompdf, 'pdf.formatos.solicitud-pf-artesanal', $templateData, "F-PR-OCC-01_Solicitud.pdf", $solicitud->id);
            } elseif ($tipoPf) {
                $archivosTemp[] = $this->generarPdf($dompdf, 'pdf.formatos.solicitud-pf', $templateData, "F-PR-OCC-02_Solicitud_Fisica.pdf", $solicitud->id);
            } else {
                $archivosTemp[] = $this->generarPdf($dompdf, 'pdf.formatos.solicitud-pm', $templateData, "F-PR-OCC-03_Solicitud_Moral.pdf", $solicitud->id);
            }

            // Ficha Técnica (todas las modalidades)
            $archivosTemp[] = $this->generarPdf($dompdf, 'pdf.formatos.ficha-tecnica', $templateData, "F-PR-OCC-04_Ficha_Tecnica.pdf", $solicitud->id);

            // Estado de Ingresos y Egresos (todas las modalidades)
            $archivosTemp[] = $this->generarPdf($dompdf, 'pdf.formatos.ingresos-egresos', $templateData, "F-PR-OCC-05_Ingresos_Egresos.pdf", $solicitud->id);

            // Declaración de Bienes Aval (solo si hay aval)
            if ($conAval) {
                $template = $isArtesanal ? 'pdf.formatos.declaracion-aval-artesanal' : 'pdf.formatos.declaracion-aval';
                $codigo   = $isArtesanal ? 'F-PR-OCC-06' : 'F-PR-OCC-07';
                $archivosTemp[] = $this->generarPdf($dompdf, $template, $templateData, "{$codigo}_Declaracion_Bienes_Aval.pdf", $solicitud->id);
            }

            // Empaquetar en ZIP
            $zipNombre  = "CREA_Formatos_Solicitud_{$solicitud->id}.zip";
            $zipRutaAbs = Storage::disk('local')->path("solicitudes/{$solicitud->id}/formatos/{$zipNombre}");

            Storage::disk('local')->makeDirectory("solicitudes/{$solicitud->id}/formatos");

            $zip = new ZipArchive();
            if ($zip->open($zipRutaAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo crear el archivo ZIP.');
            }

            foreach ($archivosTemp as [$nombreArchivo, $rutaLocal]) {
                $zip->addFile(Storage::disk('local')->path($rutaLocal), $nombreArchivo);
            }
            $zip->close();

            // Limpiar PDFs temporales
            foreach ($archivosTemp as [$_, $rutaLocal]) {
                Storage::disk('local')->delete($rutaLocal);
            }

            // Actualizar solicitud
            $solicitud->update([
                'estatus'          => 'Enviada',
                'formatos_zip_ruta'=> "solicitudes/{$solicitud->id}/formatos/{$zipNombre}",
            ]);

            DB::commit();

            return response()->json([
                'ok'          => true,
                'solicitud_id'=> $solicitud->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            // Limpiar archivos temporales si los hay
            foreach ($archivosTemp ?? [] as [$_, $rutaLocal]) {
                Storage::disk('local')->delete($rutaLocal);
            }
            return response()->json(['error' => 'Error al generar los formatos: ' . $e->getMessage()], 500);
        }
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

    // ─── Helpers privados ─────────────────────────────────────────────────────

    private function generarPdf($dompdf, string $view, array $data, string $nombreArchivo, int $solicitudId): array
    {
        $dompdf->loadView($view, $data);
        $contenido = $dompdf->output();

        $rutaLocal = "solicitudes/{$solicitudId}/formatos/tmp_{$nombreArchivo}";
        Storage::disk('local')->put($rutaLocal, $contenido);

        return [$nombreArchivo, $rutaLocal];
    }

    private function guardarAval(SolicitudCredito $solicitud, array $datos): void
    {
        AvalSolicitud::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            [
                'nombre_completo'          => $datos['nombre_completo'] ?? '',
                'correo'                   => $datos['correo'] ?? null,
                'sexo'                     => $datos['sexo'] ?? null,
                'rfc'                      => $datos['rfc'] ?? null,
                'curp'                     => $datos['curp'] ?? null,
                'telefono_celular'         => $datos['telefono_celular'] ?? null,
                'telefono_fijo'            => $datos['telefono_fijo'] ?? null,
                'edad'                     => $datos['edad'] ?? null,
                'fecha_nacimiento'         => $datos['fecha_nacimiento'] ?? null,
                'municipio_nacimiento'     => $datos['municipio_nacimiento'] ?? null,
                'municipio_residencia'     => $datos['municipio_residencia'] ?? null,
                'domicilio'                => $datos['domicilio'] ?? null,
                'colonia'                  => $datos['colonia'] ?? null,
                'cp'                       => $datos['cp'] ?? null,
                'domicilio_propio'         => (bool)($datos['domicilio_propio'] ?? true),
                'renta_mensual'            => $datos['renta_mensual'] ?? null,
                'lugar_laboral'            => $datos['lugar_laboral'] ?? null,
                'antiguedad_laboral'       => $datos['antiguedad_laboral'] ?? null,
                'ocupacion'                => $datos['ocupacion'] ?? null,
                'fecha_inicio_actividades' => $datos['fecha_inicio_actividades'] ?? null,
                'dependientes_economicos'  => $datos['dependientes_economicos'] ?? null,
                'estado_civil'             => $datos['estado_civil'] ?? null,
                'regimen_matrimonial'      => $datos['regimen_matrimonial'] ?? null,
                'nombre_conyuge'           => $datos['nombre_conyuge'] ?? null,
                'bienes_inmuebles'         => $datos['bienes_inmuebles'] ?? [],
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
