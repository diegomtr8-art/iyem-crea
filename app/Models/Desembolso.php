<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Desembolso extends Model
{
    protected $fillable = [
        'credito_id',
        'fecha_desembolso',
        'monto_desembolsado',
        'forma_desembolso',
        'banco_destino',
        'cuenta_destino',
        'folio_cheque',
        'referencia',
        'registrado_por',
        'comprobante_ruta',
        'observaciones',
    ];

    protected $casts = [
        'fecha_desembolso'   => 'date',
        'monto_desembolsado' => 'decimal:2',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
