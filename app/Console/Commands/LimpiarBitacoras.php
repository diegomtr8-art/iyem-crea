<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Borra filas viejas de tablas que crecen sin límite. Los plazos están en config/limpieza.php.
 * auditoria_log nunca se toca: es el registro de quién hizo qué con el dinero y su retención la decide dirección.
 * sessions no hace falta: Laravel ya borra las sesiones vencidas.
 */
class LimpiarBitacoras extends Command
{
    // Tabla => [clave de config/limpieza.php, variable del .env]
    private const PLAZOS = [
        'bitacora_tareas_programadas' => ['limpieza.bitacora_tareas_dias', 'LIMPIEZA_BITACORA_TAREAS_DIAS'],
        'failed_jobs'                 => ['limpieza.correos_fallidos_dias', 'LIMPIEZA_CORREOS_FALLIDOS_DIAS'],
    ];

    protected $signature   = 'crea:limpiar-bitacoras {--dry-run : Solo cuenta cuántas filas borraría, sin borrar nada}';
    protected $description = 'Borra la bitácora de tareas y los correos fallidos más viejos que sus plazos (config/limpieza.php) y el caché vencido';

    public function handle(): int
    {
        $dias = [];

        // Con 0 días o menos el corte quedaría en este momento o en el futuro y se borraría todo
        foreach (self::PLAZOS as $tabla => [$clave, $variable]) {
            $dias[$tabla] = filter_var(config($clave), FILTER_VALIDATE_INT);

            if ($dias[$tabla] === false || $dias[$tabla] < 1) {
                $this->error("El plazo de {$tabla} debe ser de al menos 1 día y está en «" . config($clave) . "». Revisa {$variable} en el .env. No se borró nada.");

                return self::FAILURE;
            }
        }

        $ahora  = now();
        $prueba = $this->option('dry-run');

        // Un solo corte para el conteo y el borrado: así --dry-run cuenta exactamente lo que se borraría
        $tablas = [
            'bitacora_tareas_programadas' => [
                DB::table('bitacora_tareas_programadas')->where('inicio', '<', $ahora->copy()->subDays($dias['bitacora_tareas_programadas'])),
                "más de {$dias['bitacora_tareas_programadas']} días",
            ],
            'failed_jobs' => [
                DB::table('failed_jobs')->where('failed_at', '<', $ahora->copy()->subDays($dias['failed_jobs'])),
                "más de {$dias['failed_jobs']} días",
            ],
            // Laravel solo borra una entrada vencida del caché cuando alguien la vuelve a leer
            config('cache.stores.database.table') => [
                DB::table(config('cache.stores.database.table'))->where('expiration', '<=', $ahora->timestamp),
                'vencidas',
            ],
        ];

        $partes = [];

        foreach ($tablas as $tabla => [$consulta, $criterio]) {
            $filas    = $prueba ? $consulta->count() : $consulta->delete();
            $partes[] = "{$tabla}: {$filas} ({$criterio})";
        }

        $resumen = ($prueba ? 'Se borrarían' : 'Se borraron') . ' ' . implode(', ', $partes) . '.';

        if ($prueba) {
            $this->info("Modo prueba, no se borró nada. {$resumen}");

            return self::SUCCESS;
        }

        // También queda en el log cuando se corre a mano, porque el panel solo registra lo que lanza el scheduler
        Log::info("crea:limpiar-bitacoras. {$resumen}");
        $this->info($resumen);

        return self::SUCCESS;
    }
}
