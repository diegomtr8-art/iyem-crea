<?php

namespace App\Console\Commands;

use App\Models\Amortizacion;
use App\Notifications\RecordatorioCuota;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecordatoriosPago extends Command
{
    protected $signature   = 'crea:recordatorios-pago';
    protected $description = 'Envía recordatorio por correo a acreditados con cuota que vence en 3 días';

    public function handle(): void
    {
        $en3dias = Carbon::now('America/Merida')->addDays(3)->toDateString();

        $cuotas = Amortizacion::with(['credito.acreditado'])
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->whereDate('fecha_vencimiento', $en3dias)
            ->get();

        $enviados = 0;

        foreach ($cuotas as $cuota) {
            $acreditado = $cuota->credito?->acreditado;
            if (!$acreditado || !$acreditado->correo) continue;

            try {
                $acreditado->notify(new RecordatorioCuota($cuota));
                $enviados++;
            } catch (\Exception $e) {
                $this->warn("No se pudo notificar a {$acreditado->nombre_completo}: {$e->getMessage()}");
            }
        }

        $this->info("Recordatorios enviados: {$enviados}");
    }
}
