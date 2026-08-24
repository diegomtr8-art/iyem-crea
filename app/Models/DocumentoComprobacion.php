<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoComprobacion extends Model
{
    protected $table = 'documentos_comprobacion';

    protected $fillable = [
        'comprobacion_id',
        'tipo',
        'descripcion',
        'monto',
        'proveedor',
        'ruta_archivo',
        'nombre_original',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function comprobacion(): BelongsTo
    {
        return $this->belongsTo(ComprobacionUso::class, 'comprobacion_id');
    }

    public function getUrlAttribute(): string
    {
        return route('portal.documentos-comprobacion.descargar', $this->id);
    }
}
