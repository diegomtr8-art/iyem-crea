<?php

namespace App\Listeners;

use App\Models\BitacoraTareaProgramada;
use App\Models\User;
use App\Notifications\TareaProgramadaFallida;
use Illuminate\Console\Application as Artisan;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class RegistrarTareaProgramada
{
    /** Fila abierta por cada tarea durante esta ejecución de schedule:run. */
    private static array $registros = [];

    public function handleStarting(ScheduledTaskStarting $event): void
    {
        $this->sinRomperElScheduler(function () use ($event) {
            $registro = BitacoraTareaProgramada::create([
                'tarea'  => $this->nombreDe($event->task),
                'inicio' => now(),
                'estado' => 'en_curso',
            ]);

            self::$registros[spl_object_id($event->task)] = $registro->id;
        });
    }

    public function handleFinished(ScheduledTaskFinished $event): void
    {
        $this->sinRomperElScheduler(function () use ($event) {
            $registro = $this->registroDe($event->task);

            if (! $registro) {
                return;
            }

            $estadoAnterior = $registro->estado;

            $registro->update([
                'fin'               => now(),
                'duracion_segundos' => $event->runtime,
                'codigo_salida'     => $event->task->exitCode,
                // Finished también llega cuando el comando falla: decide por el código de salida
                'estado'            => (int) $event->task->exitCode === 0 ? 'exito' : 'error',
            ]);

            $this->avisarSiFallo($registro, $estadoAnterior);
        });
    }

    public function handleFailed(ScheduledTaskFailed $event): void
    {
        $this->sinRomperElScheduler(function () use ($event) {
            $registro = $this->registroDe($event->task);

            if (! $registro) {
                return;
            }

            $estadoAnterior = $registro->estado;

            $registro->update([
                'fin'           => $registro->fin ?? now(),
                'estado'        => 'error',
                'mensaje_error' => Str::limit($event->exception->getMessage(), 2000),
            ]);

            $this->avisarSiFallo($registro, $estadoAnterior);
        });
    }

    /**
     * Avisa a los administradores la primera vez que una ejecución queda en error.
     * Cuando falla un comando llegan Finished y Failed de la misma ejecución: solo se avisa una vez.
     */
    private function avisarSiFallo(BitacoraTareaProgramada $registro, string $estadoAnterior): void
    {
        if ($registro->estado !== 'error' || $estadoAnterior === 'error') {
            return;
        }

        Notification::send(User::role('Administrador')->get(), new TareaProgramadaFallida($registro));
    }

    private function registroDe(Event $task): ?BitacoraTareaProgramada
    {
        $id = self::$registros[spl_object_id($task)] ?? null;

        return ($id ? BitacoraTareaProgramada::find($id) : null)
            ?? BitacoraTareaProgramada::where('tarea', $this->nombreDe($task))
                ->where('estado', 'en_curso')
                ->latest('inicio')
                ->first();
    }

    private function nombreDe(Event $task): string
    {
        if ($task->description) {
            return $task->description;
        }

        if (! $task->command) {
            return 'Closure';
        }

        return trim(str_replace([Artisan::phpBinary(), Artisan::artisanBinary()], '', $task->command));
    }

    /** Si la bitácora falla, se anota en el log pero la tarea programada sigue corriendo. */
    private function sinRomperElScheduler(callable $accion): void
    {
        try {
            $accion();
        } catch (Throwable $e) {
            Log::warning('No se pudo escribir en la bitácora de tareas programadas', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}