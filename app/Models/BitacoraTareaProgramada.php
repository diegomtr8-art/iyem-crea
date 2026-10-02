<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraTareaProgramada extends Model
{
    // Explícito: Laravel deduciría "bitacora_tarea_programadas" (mismo error que H-04)
    protected $table = 'bitacora_tareas_programadas';

    protected $fillable = [
        'tarea', 'inicio', 'fin', 'duracion_segundos', 'estado', 'codigo_salida', 'mensaje_error',
    ];

    protected function casts(): array
    {
        return [
            'inicio'            => 'datetime',
            'fin'               => 'datetime',
            'duracion_segundos' => 'decimal:2',
            'codigo_salida'     => 'integer',
        ];
    }
}
