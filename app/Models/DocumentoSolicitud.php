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
        $esArtesanal = $esEmprendedores = $esSustentable = false;

        if ($modalidadId) {
            $modalidad = \App\Models\ModalidadCrea::find($modalidadId);
            $nombre    = strtolower($modalidad?->nombre ?? '');

            $esArtesanal     = str_contains($nombre, 'artesanal');
            $esEmprendedores = str_contains($nombre, 'emprendedores');
            $esSustentable   = str_contains($nombre, 'sustentable');
        }

        return self::armarTipos(
            $esArtesanal, $esEmprendedores, $esSustentable,
            $tipoPersona, $tipoGarantia, $estadoCivil, $estadoCivilAval, $montoSolicitado
        );
    }

    /**
     * Documentos que el wizard pide a una solicitud concreta. Es la lista que
     * deben esperar tanto el wizard como Mi Expediente.
     *
     * $igualarWizard: WizardCredito.vue asume garantía 'aval' mientras el paso 1
     * no guarda tipo_garantia; Mi Expediente lo imita para que ambas pantallas
     * muestren lo mismo. La validación del wizard en el servidor no lo usa.
     */
    public static function tiposRequeridosParaSolicitud(SolicitudCredito $solicitud, bool $igualarWizard = false): array
    {
        $wizard = $solicitud->datos_wizard ?? [];

        return self::tiposRequeridos(
            $solicitud->modalidad_id,
            $solicitud->tipo_persona,
            $solicitud->tipo_garantia ?? ($igualarWizard ? 'aval' : null),
            $wizard['datos_personales_ext']['estado_civil'] ?? null,
            $solicitud->aval?->estado_civil,
            $solicitud->monto_solicitado ? (float) $solicitud->monto_solicitado : null
        );
    }

    /**
     * Catálogo completo clave => nombre legible (incluye post-aprobación).
     * Sirve para rotular documentos ya subidos aunque ya no apliquen a la
     * solicitud. Si una clave cambia de texto según la modalidad
     * (cotizaciones_proveedor), manda la lista de tiposRequeridosParaSolicitud().
     */
    public static function etiquetas(): array
    {
        $todas = [];

        foreach ([[false, true, false], [false, false, true], [true, false, false]] as [$art, $emp, $sus]) {
            foreach (['aval', 'prendaria', 'hipotecaria'] as $garantia) {
                $todas += self::armarTipos($art, $emp, $sus, 'moral', $garantia, 'Casado(a)', 'Casado(a)', 200000.0);
            }
        }

        return $todas + self::tiposPostAprobacion();
    }

    private static function armarTipos(
        bool $esArtesanal,
        bool $esEmprendedores,
        bool $esSustentable,
        ?string $tipoPersona,
        ?string $tipoGarantia,
        ?string $estadoCivil,
        ?string $estadoCivilAval,
        ?float $montoSolicitado
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

        if ($esArtesanal || $esEmprendedores || $esSustentable) {
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
