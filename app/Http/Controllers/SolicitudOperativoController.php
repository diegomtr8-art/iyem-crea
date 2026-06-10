<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\AnuncioCiudadano;
use App\Models\AuditoriaLog;
use App\Models\Credito;
use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\SolicitudEstatusHistorial;
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
                    'analista'         => $solicitud->analisis->analista?->name,
                    'fecha_analisis'   => $solicitud->analisis->fecha_analisis?->format('d/m/Y'),
                    'monto_recomendado'=> $solicitud->analisis->monto_recomendado,
                ] : null,
            ],
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
                        'Aprobada'                 => '¡Felicidades! Tu solicitud fue aprobada. Pronto te contactaremos.',
                        'Rechazada'                => 'Lamentablemente tu solicitud no fue aprobada. Contáctanos para más información.',
                        default                    => '',
                    },
                'tipo'       => $cfg['tipo'],
                'url_accion' => route('portal.solicitud.index'),
            ]);
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
                'user_email'       => $solicitud->user?->email,
            ],
            'modalidades'    => $modalidades,
            'clave_sugerida' => $claveSugerida,
        ]);
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
        ]);

        $modalidad = ModalidadCrea::findOrFail($data['modalidad_id']);
        $tasaOrdinaria = (float) $modalidad->tasa_interes;
        $tasaMoratoria = round($tasaOrdinaria * 2.5, 2);

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
                'tasa_interes_ordinario' => $tasaOrdinaria,
                'tasa_interes_moratorio' => $tasaMoratoria,
                'estatus'                => 'Activo',
            ]);

            // Generar tabla de amortización (igual que AcreditadoController)
            $monto       = (float) $data['monto_otorgado'];
            $plazo       = (int) $data['plazo_meses'];
            $tasaMensual = ($tasaOrdinaria / 100) / 12;
            $fechaInicial = Carbon::parse($data['fecha_entrega']);
            $mesesProrroga = str_contains($modalidad->nombre, 'Sustentable') ? 3 : 0;

            $cuotaFija = ($tasaMensual > 0)
                ? $monto * ($tasaMensual / (1 - pow(1 + $tasaMensual, -$plazo)))
                : $monto / $plazo;

            $saldo = $monto;
            for ($i = 1; $i <= $plazo; $i++) {
                $interes  = round($saldo * $tasaMensual, 2);
                $capital  = ($i === $plazo) ? round($saldo, 2) : round($cuotaFija - $interes, 2);
                $cuota    = round($capital + $interes, 2);
                $credito->amortizaciones()->create([
                    'numero_cuota'               => $i,
                    'fecha_vencimiento'          => $fechaInicial->copy()->addMonths($i + $mesesProrroga)->format('Y-m-d'),
                    'saldo_insoluto'             => round($saldo, 2),
                    'capital_esperado'           => $capital,
                    'interes_ordinario_esperado' => $interes,
                    'cuota_fija'                 => $cuota,
                    'pago_restante'              => $cuota,
                    'estado'                     => 'Pendiente',
                    'capital_pagado'             => 0,
                    'interes_ordinario_pagado'   => 0,
                    'interes_moratorio_pagado'   => 0,
                    'interes_moratorio_generado' => 0,
                ]);
                $saldo -= $capital;
            }

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
