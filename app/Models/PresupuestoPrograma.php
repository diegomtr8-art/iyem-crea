<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoPrograma extends Model
{
    protected $table = 'presupuesto_programa';

    protected $fillable = [
        'ejercicio_fiscal',
        'modalidad_id',
        'monto_autorizado',
        'observaciones',
        'registrado_por',
    ];

    protected $casts = [
        'monto_autorizado' => 'decimal:2',
    ];

    public function modalidad(): BelongsTo
    {
        return $this->belongsTo(ModalidadCrea::class, 'modalidad_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
