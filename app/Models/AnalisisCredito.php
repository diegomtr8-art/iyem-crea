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
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }

    public function analista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analista_id');
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
