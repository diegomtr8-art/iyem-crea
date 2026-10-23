<?php

namespace App\Services;

use App\Console\Commands\RespaldoBase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Revisa los archivos que deja crea:respaldo-base (ver docs/RESPALDO.md).
 * Lo usan crea:verificar-respaldo, que avisa si falta, y el panel de Tareas programadas.
 */
class EstadoRespaldo
{
    // El respaldo corre a diario a las 3:00; 26 h (lo pidió Diego) dan margen si un día termina un poco más tarde
    public const HORAS_VIGENCIA = 26;

    // La base vacía (solo la estructura) ya pesa 7.4 KB comprimida; con datos, cientos de KB
    public const TAMANO_MINIMO_KB = 10;

    /**
     * El respaldo más reciente y, si hay algo mal, el motivo.
     *
     * @return array{ultimo: array{archivo: string, fecha: Carbon, bytes: int, kb: float}|null, problema: string|null}
     */
    public function revisar(): array
    {
        $ultimo = $this->ultimo();

        return ['ultimo' => $ultimo, 'problema' => $this->problema($ultimo)];
    }

    private function ultimo(): ?array
    {
        $directorio = config('respaldo.directorio');

        if (! File::isDirectory($directorio)) {
            return null;
        }

        // Solo cuentan los archivos con el nombre que pone crea:respaldo-base (no los .part ni otros)
        $archivo = collect(File::files($directorio))
            ->filter(fn ($archivo) => preg_match(RespaldoBase::PATRON_NOMBRE, $archivo->getFilename()))
            ->sortByDesc(fn ($archivo) => $archivo->getMTime())
            ->first();

        return $archivo ? [
            'archivo' => $archivo->getFilename(),
            'fecha'   => Carbon::createFromTimestamp($archivo->getMTime(), 'America/Merida'),
            'bytes'   => $archivo->getSize(),
            'kb'      => round($archivo->getSize() / 1024, 1),
        ] : null;
    }

    private function problema(?array $ultimo): ?string
    {
        if (! $ultimo) {
            return 'no hay ningún archivo de respaldo en la carpeta';
        }

        if ($ultimo['fecha']->lt(now()->subHours(self::HORAS_VIGENCIA))) {
            return 'no hay respaldo de las últimas ' . self::HORAS_VIGENCIA . ' h';
        }

        if ($ultimo['bytes'] < self::TAMANO_MINIMO_KB * 1024) {
            return "el archivo más reciente pesa {$ultimo['kb']} KB y el mínimo es " . self::TAMANO_MINIMO_KB . ' KB';
        }

        return null;
    }
}
