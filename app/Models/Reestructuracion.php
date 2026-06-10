<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reestructuracion extends Model
{
    protected $fillable = [
        'credito_id',
        'fecha_reestructura',
        'motivo',
        'saldo_al_momento',
        'mora_condonada',
        'interes_condonado',
        'nuevo_plazo_meses',
        'nueva_tasa_interes',
        'nueva_fecha_inicio_pagos',
        'autorizado_por',
        'numero_resolutivo',
        'observaciones',
    ];

    protected $casts = [
        'fecha_reestructura'       => 'date',
        'nueva_fecha_inicio_pagos' => 'date',
        'saldo_al_momento'         => 'decimal:2',
        'mora_condonada'           => 'decimal:2',
        'interes_condonado'        => 'decimal:2',
        'nueva_tasa_interes'       => 'decimal:2',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class);
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
