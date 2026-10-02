<?php

namespace App\Notifications;

use App\Models\BitacoraTareaProgramada;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

// Sin ShouldQueue: en producción no hay worker de colas, así que se guarda en el momento.
class TareaProgramadaFallida extends Notification
{
    public function __construct(public BitacoraTareaProgramada $registro) {}

    /**
     * Solo se guarda en la tabla notifications; el panel la muestra con el ícono de alertas.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'bitacora_id' => $this->registro->id,
            'tarea'       => $this->registro->tarea,
            'fecha'       => $this->registro->inicio->toIso8601String(),
            'mensaje'     => Str::limit(
                $this->registro->mensaje_error ?? "Terminó con código de salida {$this->registro->codigo_salida}.",
                300
            ),
        ];
    }
}