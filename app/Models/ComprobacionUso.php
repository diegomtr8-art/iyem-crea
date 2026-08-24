<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComprobacionUso extends Model
{
    protected $table = 'comprobaciones_uso';

    protected $fillable = [
        'credito_id',
        'solicitud_id',
        'acreditado_id',
        'fecha_desembolso',
        'fecha_limite_comprobacion',
        'estatus',
        'observaciones_acreditado',
        'observaciones_operativo',
        'fecha_revision',
        'revisado_por',
        'monto_comprobado',
    ];

    protected $casts = [
        'fecha_desembolso'          => 'date',
        'fecha_limite_comprobacion' => 'date',
        'fecha_revision'            => 'date',
        'monto_comprobado'          => 'decimal:2',
    ];

    public function credito(): BelongsTo
    {
        return $this->belongsTo(Credito::class);
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudCredito::class, 'solicitud_id');
    }

    public function acreditado(): BelongsTo
    {
        return $this->belongsTo(Acreditado::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoComprobacion::class, 'comprobacion_id');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function diasRestantes(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->fecha_limite_comprobacion, false);
    }

    public function estaVencida(): bool
    {
        return $this->estatus === 'Pendiente' && $this->fecha_limite_comprobacion->isPast();
    }

    public function semaforo(): string
    {
        $dias = $this->diasRestantes();

        if ($dias <= 0) {
            return 'vencido';
        }
        if ($dias <= 7) {
            return 'rojo';
        }
        if ($dias <= 15) {
            return 'amarillo';
        }

        return 'verde';
    }
}
