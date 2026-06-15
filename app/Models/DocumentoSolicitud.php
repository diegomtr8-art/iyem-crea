<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentoSolicitud extends Model
{
    protected $table = 'documentos_solicitud';

    protected $fillable = [
        'solicitud_id',
        'tipo_documento',
        'nombre_original',
        'ruta_archivo',
        'estatus',
        'observacion',
    ];

    protected $appends = ['url'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }

    public function getUrlAttribute(): string
    {
        return route('portal.documentos.descargar', $this->id);
    }

    public static function tiposRequeridos(?int $modalidadId = null, ?string $tipoPersona = null, ?string $tipoGarantia = null): array
    {
        $base = [
            'ine_frente'           => 'INE / Credencial (Frente)',
            'ine_reverso'          => 'INE / Credencial (Reverso)',
            'curp'                 => 'Documento CURP oficial',
            'comprobante_domicilio'=> 'Comprobante de Domicilio',
            'foto_negocio'         => 'Fotografía del Negocio o Proyecto',
        ];

        if ($modalidadId) {
            $modalidad = \App\Models\ModalidadCrea::find($modalidadId);
            $nombre    = strtolower($modalidad?->nombre ?? '');

            if (str_contains($nombre, 'artesanal'))      $base['constancia_artesano']    = 'Constancia de Artesano';
            if (str_contains($nombre, 'emprendedores'))  $base['constancia_situacion']   = 'Constancia de Situación Fiscal';
            if (str_contains($nombre, 'sustentable'))    { $base['opinion_cumplimiento'] = 'Opinión de Cumplimiento SAT'; $base['plan_negocio'] = 'Plan de Negocio'; }
        }

        if ($tipoPersona === 'moral') {
            $base['acta_constitutiva'] = 'Acta Constitutiva';
            $base['poder_rep_legal']   = 'Poder del Representante Legal';
            $base['id_rep_legal']      = 'Identificación del Representante Legal';
        }

        if ($tipoGarantia === 'aval')        { $base['id_aval'] = 'Identificación del Aval'; $base['comprobante_domicilio_aval'] = 'Comprobante de Domicilio del Aval'; }
        if ($tipoGarantia === 'prendaria')   $base['factura_bien_mueble']     = 'Factura del Bien Mueble en Garantía';
        if ($tipoGarantia === 'hipotecaria') $base['doc_propiedad_inmueble']  = 'Documento de Propiedad del Inmueble';

        $base['cotizaciones'] = 'Cotizaciones del Destino del Crédito (mínimo 2)';

        return $base;
    }
}
