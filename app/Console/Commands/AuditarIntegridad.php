<?php

namespace App\Console\Commands;

use App\Services\Auditoria\Comprobacion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditarIntegridad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crea:auditar {--detalle} {--json} {--solo=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita la integridad de créditos, pagos y amortizaciones';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $comprobaciones = array_map(
            fn ($clase) => app($clase),
            config('auditoria.comprobaciones')
        );

        $solo = $this->option('solo');

        if ($solo) {
            $comprobaciones = array_values(array_filter(
                $comprobaciones,
                fn (Comprobacion $c) => $c->clave() === $solo
            ));

            if (count($comprobaciones) === 0) {
                $claves = array_map(
                    fn ($clase) => app($clase)->clave(),
                    config('auditoria.comprobaciones')
                );
                $this->error("No existe la comprobación '{$solo}'. Claves válidas: " . implode(', ', $claves));

                return self::INVALID;
            }
        }

        $resultados = array_map(fn (Comprobacion $c) => $c->ejecutar(), $comprobaciones);
        $conHallazgos = count(array_filter($resultados, fn ($r) => $r->tieneHallazgos()));
        $codigo = $conHallazgos > 0 ? self::FAILURE : self::SUCCESS;

        if ($this->option('json')) {
            $this->line(json_encode([
                'fecha' => Carbon::now('America/Merida')->toDateString(),
                'comprobaciones' => array_map(fn ($r) => $r->toArray(), $resultados),
                'con_hallazgos' => $conHallazgos,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $codigo;
        }

        $this->line('Auditoría de integridad · ' . Carbon::now('America/Merida')->format('d/m/Y'));
        $this->newLine();

        foreach ($resultados as $r) {
            if ($r->tieneHallazgos()) {
                $derecha = number_format($r->hallazgos) . ' de ' . number_format($r->revisados) . ' ' . $r->unidad;
                if ($r->monto !== null) {
                    $derecha .= ' · $' . number_format($r->monto, 2);
                }
                $derecha .= ' <comment>[ALERTA]</comment>';
            } else {
                $derecha = number_format($r->revisados) . ' ' . $r->unidad . ' <info>[OK]</info>';
            }

            $this->components->twoColumnDetail($r->nombre, $derecha);
        }

        $this->newLine();

        $mensaje = "{$conHallazgos} de " . count($resultados) . ' comprobaciones con hallazgos.';

        $conHallazgos > 0 ? $this->warn($mensaje) : $this->info($mensaje);

        if ($this->option('detalle')) {
            foreach ($resultados as $r) {
                if (! $r->tieneHallazgos()) {
                    continue;
                }

                $this->newLine();
                $this->line("<options=bold>{$r->nombre}</>");

                $filas = array_map(fn ($fila) => (array) $fila, $r->detalle);
                $this->table(array_keys($filas[0]), $filas);
            }
        }

        return $codigo;
    }
}
