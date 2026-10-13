<?php

namespace App\Http\Controllers;

use App\Models\BitacoraTareaProgramada;
use Inertia\Inertia;

class BitacoraTareasController extends Controller
{
    // Las tareas de routes/console.php. Si agregan una tarea nueva, sumarla aquí:
    // así aparece en rojo aunque nunca haya corrido.
    private const TAREAS_ESPERADAS = ['crea:update-moratorio', 'crea:recordatorios-pago'];

    private const HORAS_ALERTA = 24;

    public function index()
    {
        $ahora  = now();
        $limite = $ahora->copy()->subHours(self::HORAS_ALERTA);
        $desde  = $ahora->copy()->subDays(7);

        $nombres = collect(self::TAREAS_ESPERADAS)
            ->merge(BitacoraTareaProgramada::distinct()->pluck('tarea'))
            ->unique()->values();

        $tareas = $nombres->map(function ($nombre) use ($ahora, $limite, $desde) {
            $ultima = BitacoraTareaProgramada::where('tarea', $nombre)->latest('inicio')->first();

            return [
                'tarea'            => $nombre,
                'ultima_ejecucion' => $ultima?->inicio->timezone('America/Merida')->format('d/m/Y H:i'),
                'ultimo_estado'    => $ultima?->estado,
                'ultimo_mensaje'   => $ultima?->mensaje_error,
                'errores_7_dias'   => BitacoraTareaProgramada::where('tarea', $nombre)
                    ->where('estado', 'error')->where('inicio', '>=', $desde)->count(),
                'sin_correr'       => ! $ultima || $ultima->inicio->lt($limite),
                'horas_sin_correr' => $ultima ? (int) $ultima->inicio->diffInHours($ahora) : null,
            ];
        });

        return Inertia::render('Admin/TareasProgramadas', [
            'tareas'      => $tareas,
            'horas_alerta' => self::HORAS_ALERTA,
        ]);
    }
}