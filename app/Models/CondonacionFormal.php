<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CondonacionFormal extends Model
{
    protected $table = 'condonaciones_formales';

    protected $fillable = [
        'credito_id',
        'tipo',
        'monto_condonado_capital',
        'monto_condonado_intereses',
        'monto_condonado_mora',
        'motivo',
        'numero_acuerdo',
        'autorizado_por',
        'fecha_autorizacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_autorizacion'        => 'date',
        'monto_condonado_capital'   => 'decimal:2',
        'monto_condonado_intereses' => 'decimal:2',
        'monto_condonado_mora'      => 'decimal:2',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class);
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }

    public function montoTotal(): float
    {
        return (float) $this->monto_condonado_capital
             + (float) $this->monto_condonado_intereses
             + (float) $this->monto_condonado_mora;
    }
}
