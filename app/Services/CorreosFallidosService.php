<?php

namespace App\Services;

use App\Jobs\EnviarRecordatorioPago;
use App\Models\Amortizacion;
use App\Models\AuditoriaLog;
use Carbon\Carbon;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Correos que agotaron sus intentos y quedaron en failed_jobs (ver docs/CORREOS_FALLIDOS.md).
 * Hoy el único correo que sale por la cola es el recordatorio de pago: los de contacto
 * se envían en el momento y nunca llegan a failed_jobs.
 */
class CorreosFallidosService
{
    public const TIPOS = [
        EnviarRecordatorioPago::class => 'Recordatorio de pago',
    ];

    // Los mismos estados que revisa EnviarRecordatorioPago antes de enviar
    private const ESTADOS_SIN_AVISO = [
        'Pagado'         => 'pagada',
        'Condonado'      => 'condonada',
        'Reestructurada' => 'reestructurada',
        'Gracia'         => 'en periodo de gracia',
    ];

    public function contar(): int
    {
        return $this->consulta()->count();
    }

    /** Ids de los fallos pendientes, del más viejo al más nuevo. */
    public function ids(): Collection
    {
        return $this->consulta()->pluck('id');
    }

    /** Lo que muestra la pantalla: sin payload ni correo completo. */
    public function pendientes(): Collection
    {
        return $this->consulta()->get()
            ->map(function ($fila) {
                $cuota = $this->cuotaDelFallo($fila);

                return [
                    'id'           => $fila->id,
                    'tipo'         => $this->tipo($fila),
                    'destinatario' => self::enmascarar($cuota?->credito?->acreditado?->correo),
                    'credito'      => $cuota?->credito?->clave_contrato,
                    'cuota'        => $cuota?->numero_cuota,
                    'vence'        => $cuota?->fecha_vencimiento?->format('d/m/Y'),
                    'fecha_fallo'  => Carbon::parse($fila->failed_at)->timezone('America/Merida')->format('d/m/Y H:i'),
                    'error'        => self::resumirError($fila->exception),
                    'no_aplica'    => $this->motivoParaNoEnviar($cuota),
                ];
            })
            ->values();
    }

    /**
     * Vuelve a poner el recordatorio en la cola, solo si todavía aplica.
     * Si ya no aplica, lo descarta y dice por qué.
     *
     * @return array{enviado: bool, mensaje: string}
     */
    public function reintentar(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $fila = $this->consulta()->where('id', $id)->first();

            // Se borra antes de reenviar: si dos administradores le dan clic a la vez, solo uno lo procesa
            if (! $fila || DB::table('failed_jobs')->where('id', $id)->delete() === 0) {
                return ['enviado' => false, 'mensaje' => 'Ese correo ya no estaba en la lista: alguien más ya lo reintentó o lo descartó.'];
            }

            $cuota  = $this->cuotaDelFallo($fila);
            $motivo = $this->motivoParaNoEnviar($cuota);

            // Misma reserva que el botón y el proceso diario: si otro ya marcó la cuota, no se duplica
            if (! $motivo && DB::table('amortizaciones')->where('id', $cuota->id)->whereNull('recordatorio_enviado_at')
                    ->update(['recordatorio_enviado_at' => now()]) === 0) {
                $motivo = 'ya se le volvió a enviar el aviso';
            }

            $correo = $this->describir($fila, $cuota);

            if ($motivo) {
                $this->registrar($fila, 'deleted', "{$correo} se descartó al reintentar: {$motivo}.");

                return ['enviado' => false, 'mensaje' => "No se reenvió {$correo}: {$motivo}. Se quitó de la lista."];
            }

            EnviarRecordatorioPago::dispatch($cuota);
            $this->registrar($fila, 'updated', "{$correo} se volvió a poner en la cola de envío.");

            return ['enviado' => true, 'mensaje' => "Se volvió a poner en la cola de envío {$correo}."];
        });
    }

    /** Quita el fallo de la lista sin enviar nada. Devuelve false si ya no estaba. */
    public function descartar(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $fila = $this->consulta()->where('id', $id)->first();

            if (! $fila || DB::table('failed_jobs')->where('id', $id)->delete() === 0) {
                return false;
            }

            $correo = $this->describir($fila, $this->cuotaDelFallo($fila));
            $this->registrar($fila, 'deleted', "{$correo} se descartó sin reenviar. Error original: " . self::resumirError($fila->exception));

            return true;
        });
    }

    /** a***@dominio.com: lo suficiente para reconocerlo sin exponer la dirección. */
    public static function enmascarar(?string $correo): string
    {
        if (! $correo || ! str_contains($correo, '@')) {
            return '—';
        }

        [$usuario, $dominio] = explode('@', $correo, 2);

        return mb_substr($usuario, 0, 1) . '***@' . $dominio;
    }

    /** Primera línea del error, sin la ruta del archivo y con los correos enmascarados. */
    public static function resumirError(string $excepcion): string
    {
        $linea = strtok($excepcion, "\n") ?: '';
        // La ruta empieza con la unidad (C:\) o con /, y puede tener espacios
        $linea = preg_replace('/ in (?:[A-Za-z]:[\\\\\/]|\/).*:\d+$/', '', $linea);
        $linea = preg_replace('/^(?:\w+\\\\)+(\w+:)/', '$1', $linea);
        $linea = preg_replace_callback('/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/', fn ($m) => self::enmascarar($m[0]), $linea);

        return Str::limit($linea, 160);
    }

    private function consulta()
    {
        // displayName viene dentro del payload; basta con buscar el nombre de la clase
        return DB::table('failed_jobs')
            ->where(function ($q) {
                foreach (array_keys(self::TIPOS) as $clase) {
                    $q->orWhere('payload', 'like', '%' . class_basename($clase) . '%');
                }
            })
            ->orderBy('id');
    }

    private function tipo(object $fila): string
    {
        $clase = json_decode($fila->payload, true)['displayName'] ?? '';

        return self::TIPOS[$clase] ?? class_basename($clase);
    }

    private function cuotaDelFallo(object $fila): ?Amortizacion
    {
        $comando = json_decode($fila->payload, true)['data']['command'] ?? null;

        if (! is_string($comando)) {
            return null;
        }

        // Solo se reconstruye ModelIdentifier: leer el fallo no ejecuta código del job ni consulta la cuota
        $job = unserialize($comando, ['allowed_classes' => [ModelIdentifier::class]]);
        $id  = ((array) $job)['cuota'] ?? null;

        return $id instanceof ModelIdentifier
            ? Amortizacion::with('credito.acreditado')->find($id->id)
            : null;
    }

    // Lo que ya revisa el job (cuota sin pagar, acreditado con correo), más la fecha y la marca de aviso
    private function motivoParaNoEnviar(?Amortizacion $cuota): ?string
    {
        if (! $cuota) {
            return 'la cuota ya no existe';
        }

        if (isset(self::ESTADOS_SIN_AVISO[$cuota->estado])) {
            return 'la cuota ya está ' . self::ESTADOS_SIN_AVISO[$cuota->estado];
        }

        if (! $cuota->credito?->acreditado?->correo) {
            return 'el acreditado ya no tiene correo';
        }

        // Se comparan fechas, no horas: una cuota que vence hoy en Mérida todavía se puede avisar
        if ($cuota->fecha_vencimiento->toDateString() < Carbon::now('America/Merida')->toDateString()) {
            return 'la cuota ya venció el ' . $cuota->fecha_vencimiento->format('d/m/Y');
        }

        if ($cuota->recordatorio_enviado_at) {
            return 'ya se le volvió a enviar el aviso el '
                . Carbon::parse($cuota->recordatorio_enviado_at)->timezone('America/Merida')->format('d/m/Y H:i');
        }

        return null;
    }

    private function describir(object $fila, ?Amortizacion $cuota): string
    {
        $tipo = Str::lower($this->tipo($fila));

        return $cuota
            ? "el {$tipo} de la cuota {$cuota->numero_cuota} del crédito {$cuota->credito?->clave_contrato}"
            : "el {$tipo}";
    }

    private function registrar(object $fila, string $accion, string $descripcion): void
    {
        AuditoriaLog::registrar(
            'CorreoFallido',
            $fila->id,
            $accion,
            'estado',
            'Fallido',
            $accion === 'deleted' ? 'Descartado' : 'Reenviado',
            Str::ucfirst($descripcion)
        );
    }
}
