<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BitacoraTareaProgramada extends Model
{
    // Explícito: Laravel deduciría "bitacora_tarea_programadas" (mismo error que H-04)
    protected $table = 'bitacora_tareas_programadas';

    protected $fillable = [
        'tarea', 'inicio', 'fin', 'duracion_segundos', 'estado', 'codigo_salida', 'mensaje_error', 'resultado',
    ];

    /**
     * Guarda lo que reportó la tarea en su ejecución en curso, la que abrió RegistrarTareaProgramada.
     * Si el comando se corrió a mano no hay fila en curso y no se anota: el panel solo registra lo que lanza el scheduler.
     */
    public static function anotarResultado(string $tarea, string $resultado): void
    {
        self::where('tarea', $tarea)->where('estado', 'en_curso')->latest('inicio')->first()
            ?->update(['resultado' => Str::limit($resultado, 500)]);
    }

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
