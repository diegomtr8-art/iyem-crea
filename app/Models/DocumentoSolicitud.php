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

    public static function tiposRequeridos(
        ?int $modalidadId = null,
        ?string $tipoPersona = null,
        ?string $tipoGarantia = null,
        ?string $estadoCivil = null,
        ?string $estadoCivilAval = null,
        ?float $montoSolicitado = null
    ): array
    {
        $base = [
            'ine_frente'           => 'INE / Credencial (Frente)',
            'ine_reverso'          => 'INE / Credencial (Reverso)',
            'curp'                 => 'Documento CURP oficial',
            'comprobante_domicilio'=> 'Comprobante de Domicilio',
            'foto_negocio'         => 'Fotografía del Negocio o Proyecto',
            'acta_nacimiento'      => 'Acta de nacimiento',
            'propiedad_negocio'    => 'Documento de propiedad/posesión del negocio',
            'carta_no_servidor'    => 'Carta de no ser servidor público',
        ];

        if ($estadoCivil === 'Casado(a)') {
            $base['acta_matrimonio'] = 'Acta de matrimonio';
        }

        $esArtesanal = $esEmprendedores = $esSustentable = false;

        if ($modalidadId) {
            $modalidad = \App\Models\ModalidadCrea::find($modalidadId);
            $nombre    = strtolower($modalidad?->nombre ?? '');

            $esArtesanal     = str_contains($nombre, 'artesanal');
            $esEmprendedores = str_contains($nombre, 'emprendedores');
            $esSustentable   = str_contains($nombre, 'sustentable');

            if ($esArtesanal) {
                $base['constancia_artesano']  = 'Constancia de Artesano';
                $base['cotizaciones_proveedor'] = '2 cotizaciones de proveedores';
            }

            if ($esEmprendedores || $esSustentable) {
                $base['cotizaciones_proveedor'] = '3 cotizaciones de proveedores';
                $base['buro_credito'] = 'Reporte buró de crédito vigente (≤180 días)';
            }

            if ($esEmprendedores) {
                $base['constancia_situacion'] = 'Constancia de Situación Fiscal';
            }

            if ($esSustentable) {
                $base['opinion_cumplimiento']      = 'Opinión de Cumplimiento SAT';
                $base['plan_trabajo_sostenible']   = 'Plan de trabajo sostenible con impacto ambiental';

                if ($montoSolicitado !== null && $montoSolicitado >= 200000) {
                    $base['escritura_hipotecaria'] = 'Escritura de garantía hipotecaria';
                }
            }
        }

        if ($tipoPersona === 'moral') {
            $base['acta_constitutiva'] = 'Acta Constitutiva';
            $base['poder_rep_legal']   = 'Poder del Representante Legal';
            $base['id_rep_legal']      = 'Identificación del Representante Legal';

            if ($esEmprendedores || $esSustentable) {
                $base['balance_general'] = 'Balance general + estado de resultados';
            }
        }

        if ($tipoGarantia === 'aval') {
            $base['id_aval'] = 'Identificación del Aval';
            $base['comprobante_domicilio_aval'] = 'Comprobante de Domicilio del Aval';
            $base['acta_nacimiento_aval'] = 'Acta de nacimiento del aval';

            if ($estadoCivilAval === 'Casado(a)') {
                $base['acta_matrimonio_aval'] = 'Acta de matrimonio del aval';
            }
        }
        if ($tipoGarantia === 'prendaria')   $base['factura_bien_mueble']     = 'Factura del Bien Mueble en Garantía';
        if ($tipoGarantia === 'hipotecaria') $base['doc_propiedad_inmueble']  = 'Documento de Propiedad del Inmueble';

        return $base;
    }

    /**
     * Documentos que el ciudadano sube después de que su solicitud fue aprobada
     * (previos a la firma de formalización en oficinas IYEM).
     */
    public static function tiposPostAprobacion(): array
    {
        return [
            'foto_fachada'           => 'Fotografía de fachada del negocio',
            'google_maps_negocio'    => 'Enlace de Google Maps del negocio',
            'cuenta_bancaria_caratula'=> 'Carátula de estado de cuenta bancaria (últimos 3 meses)',
        ];
    }
}
