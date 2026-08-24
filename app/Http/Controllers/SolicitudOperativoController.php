<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\AnuncioCiudadano;
use App\Models\AuditoriaLog;
use App\Models\Credito;
use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\PresupuestoPrograma;
use App\Models\SolicitudCredito;
use App\Models\SolicitudEstatusHistorial;
use App\Services\FormatosService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SolicitudOperativoController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SolicitudCredito::with(['user', 'modalidad'])
            ->orderByDesc('updated_at');

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('nombre_completo', 'like', "%{$b}%")
                  ->orWhere('curp', 'like', "%{$b}%")
                  ->orWhere('municipio', 'like', "%{$b}%");
            });
        }

        $solicitudes = $query->paginate(20)->withQueryString();

        return Inertia::render('Solicitudes/Index', [
            'solicitudes' => $solicitudes->through(fn($s) => [
                'id'              => $s->id,
                'nombre_completo' => $s->nombre_completo,
                'curp'            => $s->curp,
                'municipio'       => $s->municipio,
                'modalidad'       => $s->modalidad?->nombre,
                'estatus'         => $s->estatus,
                'correo'          => $s->correo,
                'telefono'        => $s->telefono,
                'created_at'      => $s->created_at->format('d/m/Y'),
                'updated_at'      => $s->updated_at->format('d/m/Y'),
                'user_email'      => $s->user?->email,
                'credito_id'      => $s->credito_id,
                'acreditado_id'   => $s->acreditado_id,
            ]),
            'filters' => $request->only(['estatus', 'buscar']),
            'kpis'    => $this->calcularKpis(),
        ]);
    }

    private function calcularKpis(): array
    {
        $counts = SolicitudCredito::selectRaw('estatus, count(*) as total')
            ->groupBy('estatus')
            ->pluck('total', 'estatus');

        return [
            'total'                  => SolicitudCredito::count(),
            'enviadas'               => $counts['Enviada'] ?? 0,
            'en_revision'            => $counts['En_Revision'] ?? 0,
            'documentacion_incompleta'=> $counts['Documentacion_Incompleta'] ?? 0,
            'aprobadas'              => $counts['Aprobada'] ?? 0,
            'rechazadas'             => $counts['Rechazada'] ?? 0,
            'hoy'                    => SolicitudCredito::whereDate('created_at', today())->count(),
        ];
    }

    public function show(SolicitudCredito $solicitud): Response
    {
        $solicitud->load(['user', 'modalidad', 'documentos', 'acreditado', 'credito', 'estatusHistorial.usuario', 'analisis.analista', 'asignadoA']);

        return Inertia::render('Solicitudes/Show', [
            'solicitud' => [
                'id'               => $solicitud->id,
                'estatus'          => $solicitud->estatus,
                'observaciones'    => $solicitud->observaciones,
                'motivo_rechazo'   => $solicitud->motivo_rechazo,
                'nombre_completo'  => $solicitud->nombre_completo,
                'curp'             => $solicitud->curp,
                'rfc'              => $solicitud->rfc,
                'fecha_nacimiento' => $solicitud->fecha_nacimiento?->format('d/m/Y'),
                'sexo'             => $solicitud->sexo,
                'municipio'        => $solicitud->municipio,
                'direccion'        => $solicitud->direccion,
                'telefono'         => $solicitud->telefono,
                'correo'           => $solicitud->correo,
                'mayahablante'     => $solicitud->mayahablante,
                'discapacidad'     => $solicitud->discapacidad,
                'modalidad'        => $solicitud->modalidad?->nombre,
                'modalidad_id'     => $solicitud->modalidad_id,
                'giro_comercial'   => $solicitud->giro_comercial,
                'destino_credito'  => $solicitud->destino_credito,
                'descripcion_negocio' => $solicitud->descripcion_negocio,
                'monto_solicitado' => $solicitud->monto_solicitado,
                'alta_sat'         => $solicitud->alta_sat,
                'asignado_a'       => $solicitud->asignadoA?->name,
                'fecha_asignacion' => $solicitud->fecha_asignacion?->format('d/m/Y H:i'),
                'created_at'       => $solicitud->created_at->format('d/m/Y H:i'),
                'updated_at'       => $solicitud->updated_at->format('d/m/Y H:i'),
                'user_email'       => $solicitud->user?->email,
                'user_id'          => $solicitud->user_id,
                'acreditado_id'    => $solicitud->acreditado_id,
                'credito_id'       => $solicitud->credito_id,
                'documentos' => $solicitud->documentos->map(fn($d) => [
                    'id'             => $d->id,
                    'tipo_documento' => $d->tipo_documento,
                    'label'          => DocumentoSolicitud::tiposRequeridos()[$d->tipo_documento] ?? $d->tipo_documento,
                    'nombre_original'=> $d->nombre_original,
                    'estatus'        => $d->estatus,
                    'observacion'    => $d->observacion,
                    'url'            => $d->url,
                    'updated_at'     => $d->updated_at->format('d/m/Y'),
                ]),
                'historial_estatus' => $solicitud->estatusHistorial->map(fn($h) => [
                    'estatus_anterior' => $h->estatus_anterior,
                    'estatus_nuevo'    => $h->estatus_nuevo,
                    'observaciones'    => $h->observaciones,
                    'usuario'          => $h->usuario_nombre,
                    'fecha'            => $h->created_at->format('d/m/Y H:i'),
                ]),
                'analisis' => $solicitud->analisis ? [
                    'recomendacion'    => $solicitud->analisis->recomendacion,
                    'score_cualitativo'=> $solicitud->analisis->score_cualitativo,
                    'puntaje_total'    => $solicitud->analisis->puntaje_total,
                    'analista'         => $solicitud->analisis->analista?->name,
                    'fecha_analisis'   => $solicitud->analisis->fecha_analisis?->format('d/m/Y'),
                    'monto_recomendado'=> $solicitud->analisis->monto_recomendado,
                ] : null,
            ],
            'operativos' => \App\Models\User::where('tipo', 'operativo')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function asignar(Request $request, SolicitudCredito $solicitud): RedirectResponse
    {
        $request->validate(['asignado_a' => 'required|exists:users,id']);

        $solicitud->update([
            'asignado_a'      => $request->asignado_a,
            'fecha_asignacion'=> now(),
            'estatus'         => 'En_Revision',
        ]);

        SolicitudEstatusHistorial::create([
            'solicitud_id'    => $solicitud->id,
            'estatus_anterior'=> $solicitud->getOriginal('estatus'),
            'estatus_nuevo'   => 'En_Revision',
            'observaciones'   => 'Solicitud asignada a analista',
            'usuario_id'      => auth()->id(),
            'usuario_nombre'  => auth()->user()?->name,
            'ip_address'      => request()->ip(),
        ]);

        return back()->with('success', 'Solicitud asignada al analista.');
    }

    public function cambiarEstatus(Request $request, SolicitudCredito $solicitud): RedirectResponse
    {
        $request->validate([
            'estatus'       => 'required|in:En_Revision,Documentacion_Incompleta,Aprobada,Rechazada',
            'observaciones' => 'nullable|string|max:2000',
            'motivo_rechazo'=> 'required_if:estatus,Rechazada|nullable|string|max:500',
        ]);

        $estatusAnterior = $solicitud->estatus;

        $solicitud->update([
            'estatus'       => $request->estatus,
            'observaciones' => $request->observaciones,
            'motivo_rechazo'=> $request->estatus === 'Rechazada' ? $request->motivo_rechazo : null,
        ]);

        // Registrar en historial
        SolicitudEstatusHistorial::create([
            'solicitud_id'    => $solicitud->id,
            'estatus_anterior'=> $estatusAnterior,
            'estatus_nuevo'   => $request->estatus,
            'observaciones'   => $request->observaciones,
            'motivo_rechazo'  => $request->motivo_rechazo,
            'usuario_id'      => auth()->id(),
            'usuario_nombre'  => auth()->user()?->name,
            'ip_address'      => request()->ip(),
        ]);

        AuditoriaLog::registrar(
            'SolicitudCredito',
            $solicitud->id,
            'updated',
            'estatus',
            $estatusAnterior,
            $request->estatus,
            "Cambio de estatus por " . auth()->user()?->name
        );

        $mensajes = [
            'En_Revision'              => ['titulo' => 'Tu solicitud está en revisión', 'tipo' => 'info'],
            'Documentacion_Incompleta' => ['titulo' => 'Documentación incompleta', 'tipo' => 'alerta'],
            'Aprobada'                 => ['titulo' => '¡Tu solicitud fue aprobada!', 'tipo' => 'exito'],
            'Rechazada'                => ['titulo' => 'Tu solicitud no fue aprobada', 'tipo' => 'error'],
        ];

        $cfg = $mensajes[$request->estatus] ?? null;
        if ($cfg) {
            AnuncioCiudadano::create([
                'user_id' => $solicitud->user_id,
                'titulo'  => $cfg['titulo'],
                'mensaje' => $request->observaciones
                    ?? match ($request->estatus) {
                        'En_Revision'              => 'Un asesor CREA está revisando tu solicitud y documentos.',
                        'Documentacion_Incompleta' => 'Algunos documentos requieren corrección. Revisa tu solicitud.',
                        'Aprobada'                 => '¡Felicidades! Tu solicitud fue aprobada. Ya puedes descargar tus formatos.',
                        'Rechazada'                => 'Lamentablemente tu solicitud no fue aprobada. Contáctanos para más información.',
                        default                    => '',
                    },
                'tipo'       => $cfg['tipo'],
                'url_accion' => route('portal.solicitud.index'),
            ]);
        }

        // Al aprobar, generar ZIP con los formatos oficiales
        if ($request->estatus === 'Aprobada') {
            try {
                $zipRuta = FormatosService::generarZip($solicitud);
                $solicitud->update(['formatos_zip_ruta' => $zipRuta]);
            } catch (\Throwable $e) {
                // No bloquear el cambio de estatus si falla la generación del ZIP
                logger()->error('Error generando ZIP para solicitud ' . $solicitud->id . ': ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Estatus actualizado y notificación enviada al ciudadano.');
    }

    public function revisarDocumento(Request $request, DocumentoSolicitud $documento): RedirectResponse
    {
        $request->validate([
            'estatus'    => 'required|in:Aprobado,Rechazado',
            'observacion'=> 'required_if:estatus,Rechazado|nullable|string|max:500',
        ]);

        $documento->update([
            'estatus'    => $request->estatus,
            'observacion'=> $request->observacion,
        ]);

        $solicitud = $documento->solicitud;

        if ($request->estatus === 'Rechazado') {
            AnuncioCiudadano::create([
                'user_id' => $solicitud->user_id,
                'titulo'  => 'Documento requiere corrección',
                'mensaje' => "El documento \"{$documento->nombre_original}\" fue rechazado. Motivo: {$request->observacion}",
                'tipo'    => 'alerta',
                'url_accion' => route('portal.solicitud.index'),
            ]);
        } elseif ($request->estatus === 'Aprobado') {
            $todosAprobados = $solicitud->documentos()->where('estatus', '!=', 'Aprobado')->doesntExist();
            if ($todosAprobados) {
                AnuncioCiudadano::create([
                    'user_id' => $solicitud->user_id,
                    'titulo'  => '¡Todos tus documentos están aprobados!',
                    'mensaje' => 'Todos tus documentos fueron verificados correctamente. Tu solicitud continúa en proceso.',
                    'tipo'    => 'exito',
                ]);
            }
        }

        return back()->with('success', "Documento marcado como {$request->estatus}.");
    }

    public function enviarAnuncio(Request $request, SolicitudCredito $solicitud): RedirectResponse
    {
        $request->validate([
            'titulo'  => 'required|string|max:255',
            'mensaje' => 'required|string|max:2000',
            'tipo'    => 'required|in:info,alerta,pago,exito,error',
        ]);

        AnuncioCiudadano::create([
            'user_id' => $solicitud->user_id,
            'titulo'  => $request->titulo,
            'mensaje' => $request->mensaje,
            'tipo'    => $request->tipo,
        ]);

        return back()->with('success', 'Anuncio enviado al ciudadano.');
    }

    public function registrarCredito(SolicitudCredito $solicitud): Response
    {
        abort_if($solicitud->estatus !== 'Aprobada', 403, 'Solo se puede registrar crédito en solicitudes aprobadas.');
        abort_if($solicitud->credito_id, 422, 'Esta solicitud ya tiene un crédito registrado.');

        $solicitud->load(['user', 'modalidad']);
        $modalidades = ModalidadCrea::orderBy('nombre')->get(['id', 'nombre', 'tasa_interes']);

        // Sugerir siguiente clave de contrato
        $lastClave = Credito::orderByDesc('id')->value('clave_contrato') ?? 'CREA-2025-000';
        preg_match('/(\d{4})-(\d+)$/', $lastClave, $m);
        $nextNum   = isset($m[2]) ? str_pad((int)$m[2] + 1, 3, '0', STR_PAD_LEFT) : '001';
        $claveSugerida = 'CREA-' . now()->year . '-' . $nextNum;

        $tasaPreview = null;
        if ($solicitud->modalidad) {
            [$tasaOrdinaria, $tasaMoratoria] = $this->calcularTasas($solicitud, $solicitud->modalidad);
            $tasaPreview = ['ordinaria' => $tasaOrdinaria, 'moratoria' => $tasaMoratoria];
        }

        return Inertia::render('Solicitudes/RegistrarCredito', [
            'solicitud' => [
                'id'               => $solicitud->id,
                'nombre_completo'  => $solicitud->nombre_completo,
                'curp'             => $solicitud->curp,
                'rfc'              => $solicitud->rfc,
                'sexo'             => $solicitud->sexo === 'M' ? 'H' : 'M',
                'municipio'        => $solicitud->municipio,
                'direccion'        => $solicitud->direccion,
                'correo'           => $solicitud->correo,
                'modalidad_id'     => $solicitud->modalidad_id,
                'modalidad_nombre' => $solicitud->modalidad?->nombre,
                'monto_solicitado' => $solicitud->monto_solicitado,
                'tipo_garantia'    => $solicitud->tipo_garantia,
                'user_email'       => $solicitud->user?->email,
            ],
            'modalidades'        => $modalidades,
            'clave_sugerida'     => $claveSugerida,
            'tasa_preview'       => $tasaPreview,
            'presupuesto_status' => $solicitud->modalidad_id
                ? $this->verificarPresupuesto((int) $solicitud->modalidad_id, (float) $solicitud->monto_solicitado)
                : null,
        ]);
    }

    /**
     * Calcula la tasa ordinaria (con el recargo por renovación si aplica) y la
     * tasa moratoria (ordinaria × 2.5) para una solicitud y modalidad dadas.
     *
     * @return array{0: float, 1: float}
     */
    private function calcularTasas(SolicitudCredito $solicitud, ModalidadCrea $modalidad): array
    {
        $tasaOrdinaria = (float) $modalidad->tasa_interes;

        $esRenovacion = (bool) ($solicitud->datos_wizard['es_renovacion'] ?? false);
        if ($esRenovacion) {
            $nombreMod = strtolower($modalidad->nombre);
            if (str_contains($nombreMod, 'emprendedores') || str_contains($nombreMod, 'sustentable')) {
                $tasaOrdinaria = round($tasaOrdinaria + 1, 2);
            }
        }

        return [$tasaOrdinaria, round($tasaOrdinaria * 2.5, 2)];
    }

    private function verificarPresupuesto(int $modalidadId, float $monto): array
    {
        $anio = now()->year;
        $presupuesto = PresupuestoPrograma::where('modalidad_id', $modalidadId)
            ->where('ejercicio_fiscal', $anio)
            ->first();

        if (!$presupuesto) {
            return ['alerta' => true, 'mensaje' => "No hay presupuesto registrado para esta modalidad en {$anio}."];
        }

        $ejercido = Credito::where('modalidad_id', $modalidadId)
            ->where('estatus', '!=', 'Cancelado')
            ->whereYear('fecha_entrega', $anio)
            ->sum('monto_otorgado');

        $disponible = (float) $presupuesto->monto_autorizado - (float) $ejercido;

        if ($monto > $disponible) {
            return [
                'bloqueado' => true,
                'mensaje'   => 'El monto solicitado ($' . number_format($monto, 2) . ') supera el presupuesto disponible ($' . number_format($disponible, 2) . ') para esta modalidad.',
            ];
        }

        if ($disponible < ((float) $presupuesto->monto_autorizado * 0.1)) {
            return [
                'alerta'  => true,
                'mensaje' => '⚠️ Presupuesto casi agotado. Disponible: $' . number_format($disponible, 2) . '.',
            ];
        }

        return ['ok' => true, 'disponible' => $disponible];
    }

    public function guardarCredito(Request $request, SolicitudCredito $solicitud): RedirectResponse
    {
        abort_if($solicitud->estatus !== 'Aprobada', 403);
        abort_if($solicitud->credito_id, 422, 'Ya tiene un crédito registrado.');

        $data = $request->validate([
            'nombre_completo' => 'required|string|max:255',
            'curp'            => 'nullable|string|max:18',
            'rfc'             => 'nullable|string|max:13',
            'sexo'            => 'required|in:H,M',
            'municipio'       => 'required|string|max:255',
            'direccion'       => 'nullable|string|max:500',
            'correo'          => 'nullable|email',
            'modalidad_id'    => 'required|exists:modalidad_creas,id',
            'clave_contrato'  => 'required|string|unique:creditos,clave_contrato',
            'monto_otorgado'  => 'required|numeric|min:100',
            'plazo_meses'     => 'required|integer|min:1|max:60',
            'fecha_entrega'   => 'required|date',
            'fecha_contrato'  => 'nullable|date',
        ]);

        $modalidad = ModalidadCrea::findOrFail($data['modalidad_id']);

        $presupuesto = $this->verificarPresupuesto((int) $data['modalidad_id'], (float) $data['monto_otorgado']);
        if (!empty($presupuesto['bloqueado'])) {
            return back()->withErrors(['monto_otorgado' => $presupuesto['mensaje']])->withInput();
        }

        [$tasaOrdinaria, $tasaMoratoria] = $this->calcularTasas($solicitud, $modalidad);

        return DB::transaction(function () use ($data, $solicitud, $modalidad, $tasaOrdinaria, $tasaMoratoria) {
            $acreditado = Acreditado::create([
                'nombre_completo'  => $data['nombre_completo'],
                'curp'             => strtoupper($data['curp'] ?? ''),
                'rfc'              => strtoupper($data['rfc'] ?? ''),
                'sexo'             => $data['sexo'],
                'municipio'        => $data['municipio'],
                'direccion_fiscal' => $data['direccion'],
                'correo'           => $data['correo'],
            ]);

            $credito = $acreditado->creditos()->create([
                'modalidad_id'           => $modalidad->id,
                'clave_contrato'         => $data['clave_contrato'],
                'monto_otorgado'         => $data['monto_otorgado'],
                'plazo_meses'            => $data['plazo_meses'],
                'fecha_entrega'          => $data['fecha_entrega'],
                'fecha_contrato'         => $data['fecha_contrato'] ?? $data['fecha_entrega'],
                'tasa_interes_ordinario' => $tasaOrdinaria,
                'tasa_interes_moratorio' => $tasaMoratoria,
                'estatus'                => 'Activo',
            ]);

            // Generar tabla de amortización (incluye período de gracia Sustentable)
            (new \App\Services\CreditService())->generarTablaAmortizacion(
                $credito,
                (float) $data['monto_otorgado'],
                (int) $data['plazo_meses'],
                $tasaOrdinaria,
                Carbon::parse($data['fecha_entrega']),
                $modalidad->nombre
            );

            // Vincular solicitud → acreditado y crédito
            $solicitud->update([
                'acreditado_id' => $acreditado->id,
                'credito_id'    => $credito->id,
            ]);

            // Notificar al ciudadano
            AnuncioCiudadano::create([
                'user_id' => $solicitud->user_id,
                'titulo'  => '¡Tu crédito está activo!',
                'mensaje' => "Hemos registrado tu crédito CREA ({$data['clave_contrato']}) por " .
                             '$' . number_format($data['monto_otorgado'], 2) . '. Ya puedes ver tu tabla de amortización.',
                'tipo'    => 'exito',
                'url_accion' => route('portal.credito'),
            ]);

            return redirect()->route('acreditados.show', $acreditado->id)
                ->with('success', "Crédito {$data['clave_contrato']} registrado exitosamente.");
        });
    }
}
