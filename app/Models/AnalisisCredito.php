<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalisisCredito extends Model
{
    protected $table = 'analisis_credito';

    protected $fillable = [
        'solicitud_id',
        'analista_id',
        'fecha_analisis',
        'ingresos_mensuales',
        'gastos_mensuales',
        'capacidad_pago',
        'relacion_deuda_ingreso',
        'curp_valida',
        'rfc_activo',
        'municipio_elegible',
        'giro_elegible',
        'beneficiario_duplicado',
        'alta_sat_verificada',
        'documentos_completos',
        'referencias_verificadas',
        'antiguedad_negocio_meses',
        'score_cualitativo',
        'puntaje_normativa',
        'puntaje_tecnica',
        'puntaje_financiera',
        'puntaje_impacto_ambiental',
        'puntaje_total',
        'puntaje_detalle',
        'observaciones_analisis',
        'recomendacion',
        'monto_recomendado',
        'motivo_rechazo',
    ];

    protected $casts = [
        'fecha_analisis'         => 'date',
        'ingresos_mensuales'     => 'decimal:2',
        'gastos_mensuales'       => 'decimal:2',
        'capacidad_pago'         => 'decimal:2',
        'relacion_deuda_ingreso' => 'decimal:2',
        'monto_recomendado'      => 'decimal:2',
        'curp_valida'            => 'boolean',
        'rfc_activo'             => 'boolean',
        'municipio_elegible'     => 'boolean',
        'giro_elegible'          => 'boolean',
        'beneficiario_duplicado' => 'boolean',
        'alta_sat_verificada'    => 'boolean',
        'documentos_completos'   => 'boolean',
        'referencias_verificadas'=> 'boolean',
        'puntaje_detalle'        => 'array',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_id');
    }

    /**
     * Tablas de puntaje oficiales por modalidad (Arts. 11, 19, 27 del Acuerdo).
     * Mínimo para aprobar en todas las modalidades: 60 de 100 puntos.
     */
    public static function categoriasPuntajePorModalidad(string $modalidad): array
    {
        $nombre = strtolower($modalidad);

        if (str_contains($nombre, 'artesanal')) {
            return ['normativa' => 45, 'tecnica' => 40, 'financiera' => 15];
        }
        if (str_contains($nombre, 'emprendedores')) {
            return ['normativa' => 20, 'tecnica' => 50, 'financiera' => 30];
        }
        if (str_contains($nombre, 'sustentable')) {
            return ['normativa' => 10, 'financiera' => 40, 'impacto_ambiental' => 50];
        }

        return [];
    }

    public const PUNTAJE_MINIMO_APROBACION = 60;

    /**
     * Rúbrica oficial de subcriterios por modalidad (Arts. 11, 19, 27 del Acuerdo).
     * Cada entrada suma dentro de su categoría hasta el máximo definido en
     * categoriasPuntajePorModalidad().
     */
    public static function subcriteriosPorModalidad(string $modalidad): array
    {
        $nombre = strtolower($modalidad);

        if (str_contains($nombre, 'artesanal')) {
            return [
                ['clave' => 'municipio_prioritario', 'categoria' => 'normativa', 'label' => 'Municipio de atención prioritaria', 'max' => 10],
                ['clave' => 'situacion_juridica',     'categoria' => 'normativa', 'label' => 'Situación jurídica del solicitante', 'max' => 10],
                ['clave' => 'artesano_certificado',   'categoria' => 'normativa', 'label' => 'Artesano con constancia certificada', 'max' => 25],
                ['clave' => 'viabilidad_negocio',     'categoria' => 'tecnica',   'label' => 'Viabilidad del negocio/proyecto', 'max' => 20],
                ['clave' => 'destino_credito',        'categoria' => 'tecnica',   'label' => 'Destino del crédito adecuado', 'max' => 10],
                ['clave' => 'experiencia_giro',       'categoria' => 'tecnica',   'label' => 'Experiencia en el giro', 'max' => 10],
                ['clave' => 'capacidad_pago',         'categoria' => 'financiera','label' => 'Capacidad de pago demostrada', 'max' => 10],
                ['clave' => 'solvencia_aval',         'categoria' => 'financiera','label' => 'Solvencia del aval', 'max' => 5],
            ];
        }
        if (str_contains($nombre, 'emprendedores')) {
            return [
                ['clave' => 'municipio_atencion',   'categoria' => 'normativa', 'label' => 'Municipio de atención', 'max' => 10],
                ['clave' => 'situacion_juridica',   'categoria' => 'normativa', 'label' => 'Situación jurídica', 'max' => 10],
                ['clave' => 'viabilidad_tecnica',   'categoria' => 'tecnica',   'label' => 'Viabilidad técnica', 'max' => 15],
                ['clave' => 'destino_credito',      'categoria' => 'tecnica',   'label' => 'Destino del crédito', 'max' => 10],
                ['clave' => 'experiencia_giro',     'categoria' => 'tecnica',   'label' => 'Experiencia en el giro', 'max' => 15],
                ['clave' => 'empleos_imss',         'categoria' => 'tecnica',   'label' => 'Empleos formales IMSS', 'max' => 10],
                ['clave' => 'capacidad_pago',       'categoria' => 'financiera','label' => 'Capacidad de pago', 'max' => 15],
                ['clave' => 'historial_buro',       'categoria' => 'financiera','label' => 'Historial crediticio Buró', 'max' => 15],
            ];
        }
        if (str_contains($nombre, 'sustentable')) {
            return [
                ['clave' => 'situacion_juridica',        'categoria' => 'normativa',        'label' => 'Situación jurídica', 'max' => 5],
                ['clave' => 'municipio',                 'categoria' => 'normativa',        'label' => 'Municipio', 'max' => 5],
                ['clave' => 'capacidad_pago',             'categoria' => 'financiera',       'label' => 'Capacidad de pago', 'max' => 20],
                ['clave' => 'historial_crediticio',       'categoria' => 'financiera',       'label' => 'Historial crediticio', 'max' => 20],
                ['clave' => 'certificaciones_ambientales','categoria' => 'impacto_ambiental','label' => 'Certificaciones ambientales', 'max' => 20],
                ['clave' => 'plan_trabajo_sostenible',    'categoria' => 'impacto_ambiental','label' => 'Plan de trabajo sostenible', 'max' => 20],
                ['clave' => 'empleos_verdes',             'categoria' => 'impacto_ambiental','label' => 'Generación empleos verdes', 'max' => 10],
            ];
        }

        return [];
    }

    public function calcularScore(): int
    {
        $score = 0;
        if ($this->curp_valida)           $score += 15;
        if ($this->rfc_activo)            $score += 10;
        if ($this->municipio_elegible)    $score += 10;
        if ($this->giro_elegible)         $score += 10;
        if (!$this->beneficiario_duplicado) $score += 15;
        if ($this->alta_sat_verificada)   $score += 10;
        if ($this->documentos_completos)  $score += 15;
        if ($this->referencias_verificadas) $score += 10;

        // Bonus por antigüedad del negocio
        if ($this->antiguedad_negocio_meses >= 12) $score += 5;

        return min($score, 100);
    }
}
