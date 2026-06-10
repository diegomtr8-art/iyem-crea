<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudEstatusHistorial extends Model
{
    protected $table = 'solicitud_estatus_historial';

    protected $fillable = [
        'solicitud_id',
        'estatus_anterior',
        'estatus_nuevo',
        'observaciones',
        'motivo_rechazo',
        'usuario_id',
        'usuario_nombre',
        'ip_address',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
