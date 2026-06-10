<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentoSolicitud;
use Inertia\Inertia;
use Inertia\Response;

class ExpedienteController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito()->with(['documentos', 'modalidad'])->first();

        $documentos = null;
        if ($solicitud) {
            $documentos = $solicitud->documentos->map(fn($d) => [
                'id'             => $d->id,
                'tipo_documento' => $d->tipo_documento,
                'label'          => DocumentoSolicitud::tiposRequeridos()[$d->tipo_documento] ?? $d->tipo_documento,
                'nombre_original'=> $d->nombre_original,
                'estatus'        => $d->estatus,
                'observacion'    => $d->observacion,
                'url'            => $d->url,
                'updated_at'     => $d->updated_at->format('d/m/Y'),
            ]);
        }

        return Inertia::render('portal/Expediente', [
            'solicitud'       => $solicitud ? [
                'id'              => $solicitud->id,
                'estatus'         => $solicitud->estatus,
                'nombre_completo' => $solicitud->nombre_completo,
                'curp'            => $solicitud->curp,
                'rfc'             => $solicitud->rfc,
                'fecha_nacimiento'=> $solicitud->fecha_nacimiento?->format('d/m/Y'),
                'sexo'            => $solicitud->sexo,
                'mayahablante'    => $solicitud->mayahablante,
                'discapacidad'    => $solicitud->discapacidad,
                'municipio'       => $solicitud->municipio,
                'direccion'       => $solicitud->direccion,
                'telefono'        => $solicitud->telefono,
                'correo'          => $solicitud->correo,
                'modalidad'       => $solicitud->modalidad?->nombre,
                'giro_comercial'  => $solicitud->giro_comercial,
                'destino_credito' => $solicitud->destino_credito,
                'descripcion_negocio' => $solicitud->descripcion_negocio,
                'alta_sat'        => $solicitud->alta_sat,
                'created_at'      => $solicitud->created_at->format('d/m/Y'),
            ] : null,
            'documentos'      => $documentos,
            'tipos_documentos'=> DocumentoSolicitud::tiposRequeridos(),
            'usuario'         => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
