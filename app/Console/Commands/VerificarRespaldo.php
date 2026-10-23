<?php

namespace App\Console\Commands;

use App\Services\EstadoRespaldo;
use Illuminate\Console\Command;

class VerificarRespaldo extends Command
{
    protected $signature   = 'crea:verificar-respaldo';
    protected $description = 'Revisa que haya un respaldo de la base de las últimas ' . EstadoRespaldo::HORAS_VIGENCIA . ' h y que no esté vacío';

    public function handle(EstadoRespaldo $respaldos): int
    {
        ['ultimo' => $ultimo, 'problema' => $problema] = $respaldos->revisar();

        $detalle = $ultimo
            ? "{$ultimo['archivo']} ({$ultimo['kb']} KB), del {$ultimo['fecha']->format('d/m/Y H:i')}"
            : null;

        // Al fallar, el scheduler lleva este texto a la bitácora y a la alerta de los administradores
        if ($problema) {
            $this->error("Falta respaldo: {$problema}." . ($detalle ? " Último respaldo: {$detalle}." : ''));

            return self::FAILURE;
        }

        $this->info("OK: {$detalle}.");

        return self::SUCCESS;
    }
}
