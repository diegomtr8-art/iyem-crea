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
        ?float $montoSolicitado = null,
        bool $mismoDomicilioNegocio = false
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
            $tipoPersona, $tipoGarantia, $estadoCivil, $estadoCivilAval, $montoSolicitado,
            $mismoDomicilioNegocio
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
            $solicitud->monto_solicitado ? (float) $solicitud->monto_solicitado : null,
            (bool) ($wizard['datos_negocio_ext']['mismo_domicilio'] ?? false)
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

        // Persona moral ya no pide INE, CURP ni actas del solicitante: hay que
        // recorrer también la física para no perder esos nombres.
        foreach ([[false, true, false], [false, false, true], [true, false, false]] as [$art, $emp, $sus]) {
            foreach (['fisica', 'moral'] as $persona) {
                foreach (['aval', 'prendaria', 'hipotecaria'] as $garantia) {
                    $todas += self::armarTipos($art, $emp, $sus, $persona, $garantia, 'Casado(a)', 'Casado(a)', 200000.0, false);
                }
            }
        }

        return $todas + self::tiposPostAprobacion();
    }

    /**
     * Documentos por modalidad, tipo de persona y garantía, según las Reglas de
     * Operación (Arts. 10, 18 y 26; D.14). La lista está duplicada en
     * tiposDocRequeridos de resources/js/Pages/portal/WizardCredito.vue: cualquier
     * cambio debe hacerse en los dos.
     */
    private static function armarTipos(
        bool $esArtesanal,
        bool $esEmprendedores,
        bool $esSustentable,
        ?string $tipoPersona,
        ?string $tipoGarantia,
        ?string $estadoCivil,
        ?string $estadoCivilAval,
        ?float $montoSolicitado,
        bool $mismoDomicilioNegocio = false
    ): array
    {
        $esMoral = $tipoPersona === 'moral';
        $base = [];

        // Persona moral: no se piden INE, CURP ni actas del solicitante; se pide
        // la identificación del representante legal (más abajo).
        if (!$esMoral) {
            $base['ine_frente']      = 'INE / Credencial (Frente)';
            $base['ine_reverso']     = 'INE / Credencial (Reverso)';
            $base['curp']            = 'Documento CURP oficial';
        }

        $base['comprobante_domicilio'] = 'Comprobante de Domicilio';

        // Arts. 10-c, 18 y 26: domicilio del solicitante Y del negocio; basta uno
        // si el negocio opera en el mismo domicilio.
        if (!$mismoDomicilioNegocio) {
            $base['comprobante_domicilio_negocio'] = 'Comprobante de domicilio del negocio';
        }

        $base['foto_negocio'] = 'Fotografía del Negocio o Proyecto';

        if (!$esMoral) {
            $base['acta_nacimiento'] = 'Acta de nacimiento';
        }

        $base['propiedad_negocio'] = 'Documento de propiedad/posesión del negocio';
        $base['carta_no_servidor'] = 'Carta de no ser servidor público';

        // El Art. 10-I (Artesanal) no pide el acta de matrimonio del solicitante.
        if ($estadoCivil === 'Casado(a)' && !$esMoral && !$esArtesanal) {
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
                $base['constancia_situacion'] = 'Constancia de Situación Fiscal';
            }

            if ($esSustentable) {
                $base['opinion_cumplimiento']      = 'Opinión de Cumplimiento SAT';
                $base['plan_trabajo_sostenible']   = 'Plan de trabajo sostenible con impacto ambiental';
            }
        }

        if ($esMoral) {
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

            // Arts. 18-II-f y 26-II-f: el aval acredita un bien (no aplica a Artesanal).
            if ($esEmprendedores || $esSustentable) {
                $base['doc_bien_aval'] = 'Escritura, título, certificado o factura del bien del aval';
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
