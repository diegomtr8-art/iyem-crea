<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SolicitudCreditoController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with(['documentos', 'modalidad'])->first();
        $modalidades = ModalidadCrea::all(['id', 'nombre', 'tasa_interes']);

        $documentosFormateados = null;
        if ($solicitud) {
            $documentosFormateados = $solicitud->documentos->map(fn($d) => [
                'id'             => $d->id,
                'tipo_documento' => $d->tipo_documento,
                'nombre_original'=> $d->nombre_original,
                'estatus'        => $d->estatus,
                'observacion'    => $d->observacion,
                'url'            => $d->url,
            ])->keyBy('tipo_documento');
        }

        return Inertia::render('portal/Solicitud', [
            'solicitud'   => $solicitud ? array_merge($solicitud->toArray(), [
                'documentos' => $documentosFormateados,
            ]) : null,
            'modalidades' => $modalidades,
            'tipos_documentos' => DocumentoSolicitud::tiposRequeridos(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if ($user->solicitudCredito()->exists()) {
            return back()->with('error', 'Ya tienes una solicitud registrada.');
        }

        $data = $request->validate([
            'nombre_completo'    => 'required|string|max:255',
            'curp'               => 'required|string|size:18',
            'rfc'                => 'nullable|string|max:13',
            'fecha_nacimiento'   => 'required|date|before:today',
            'sexo'               => 'required|in:M,F',
            'mayahablante'       => 'boolean',
            'discapacidad'       => 'boolean',
            'municipio'          => 'required|string|max:255',
            'direccion'          => 'required|string|max:1000',
            'telefono'           => 'required|string|max:20',
            'correo'             => 'required|email|max:255',
            'modalidad_id'       => 'required|exists:modalidad_creas,id',
            'giro_comercial'     => 'required|string|max:255',
            'destino_credito'    => 'required|string|max:2000',
            'descripcion_negocio'=> 'required|string|max:3000',
            'alta_sat'           => 'boolean',
        ]);

        $modalidad = ModalidadCrea::findOrFail($data['modalidad_id']);
        $request->validate([
            'monto_solicitado' => 'nullable|numeric|min:' . $modalidad->monto_minimo . '|max:' . $modalidad->monto_maximo,
        ]);
        $data['monto_solicitado'] = $request->monto_solicitado;

        $data['user_id'] = $user->id;
        $data['estatus'] = 'Borrador';

        $solicitud = SolicitudCredito::create($data);

        return redirect()->route('portal.solicitud.index')
            ->with('success', 'Solicitud guardada. Ahora sube tus documentos y envíala para revisión.');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;

        if (!$solicitud || !in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return back()->with('error', 'No puedes modificar tu solicitud en este momento.');
        }

        $data = $request->validate([
            'nombre_completo'    => 'required|string|max:255',
            'curp'               => 'required|string|size:18',
            'rfc'                => 'nullable|string|max:13',
            'fecha_nacimiento'   => 'required|date|before:today',
            'sexo'               => 'required|in:M,F',
            'mayahablante'       => 'boolean',
            'discapacidad'       => 'boolean',
            'municipio'          => 'required|string|max:255',
            'direccion'          => 'required|string|max:1000',
            'telefono'           => 'required|string|max:20',
            'correo'             => 'required|email|max:255',
            'modalidad_id'       => 'required|exists:modalidad_creas,id',
            'giro_comercial'     => 'required|string|max:255',
            'destino_credito'    => 'required|string|max:2000',
            'descripcion_negocio'=> 'required|string|max:3000',
            'alta_sat'           => 'boolean',
        ]);

        $modalidad = ModalidadCrea::findOrFail($data['modalidad_id']);
        $request->validate([
            'monto_solicitado' => 'nullable|numeric|min:' . $modalidad->monto_minimo . '|max:' . $modalidad->monto_maximo,
        ]);
        $data['monto_solicitado'] = $request->monto_solicitado;

        $solicitud->update($data);

        return back()->with('success', 'Solicitud actualizada correctamente.');
    }

    public function subirDocumento(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;

        if (!$solicitud || !in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return back()->with('error', 'No puedes subir documentos en este momento.');
        }

        $tiposPermitidos = array_keys(DocumentoSolicitud::tiposRequeridos(
            $solicitud->modalidad_id,
            $solicitud->tipo_persona,
            $solicitud->tipo_garantia,
        ));

        $request->validate([
            'tipo_documento' => 'required|in:' . implode(',', $tiposPermitidos),
            'archivo'        => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $archivo = $request->file('archivo');
        $tipo = $request->tipo_documento;
        $extension = $archivo->getClientOriginalExtension();
        $ruta = $archivo->storeAs(
            "solicitudes/{$solicitud->id}",
            "{$tipo}.{$extension}",
            'local'
        );

        // Reemplaza el documento si ya existía
        $solicitud->documentos()->where('tipo_documento', $tipo)->delete();

        DocumentoSolicitud::create([
            'solicitud_id'   => $solicitud->id,
            'tipo_documento' => $tipo,
            'nombre_original'=> $archivo->getClientOriginalName(),
            'ruta_archivo'   => $ruta,
            'estatus'        => 'Pendiente',
        ]);

        return back()->with('success', 'Documento subido correctamente.');
    }

    public function enviar(): RedirectResponse
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with('documentos')->first();

        if (!$solicitud || !in_array($solicitud->estatus, ['Borrador', 'Documentacion_Incompleta'])) {
            return back()->with('error', 'No puedes enviar tu solicitud en este momento.');
        }

        $tiposRequeridos = array_keys(DocumentoSolicitud::tiposRequeridos(
            $solicitud->modalidad_id,
            $solicitud->tipo_persona,
            $solicitud->tipo_garantia,
        ));
        $tiposSubidos = $solicitud->documentos->pluck('tipo_documento')->toArray();
        $faltantes = array_diff($tiposRequeridos, $tiposSubidos);

        if (!empty($faltantes)) {
            return back()->with('error', 'Debes subir todos los documentos requeridos antes de enviar.');
        }

        $solicitud->update(['estatus' => 'Enviada']);

        return redirect()->route('portal.dashboard')
            ->with('success', '¡Solicitud enviada! El equipo CREA la revisará en breve. Te notificaremos por esta plataforma.');
    }
}
