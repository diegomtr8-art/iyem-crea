<?php

namespace App\Console\Commands;

use App\Jobs\EnviarRecordatorioPago;
use App\Models\Amortizacion;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecordatoriosPago extends Command
{
    // Días de anticipación del aviso. El botón del administrador usa este mismo límite.
    public const DIAS_ANTICIPACION = 3;
    protected $signature   = 'crea:recordatorios-pago';
    protected $description = 'Encola el recordatorio por correo a acreditados con cuota que vence en 3 días';

    public function handle(): void
    {
        $en3dias = Carbon::now('America/Merida')->addDays(self::DIAS_ANTICIPACION)->toDateString();

        $cuotas = Amortizacion::with(['credito.acreditado'])
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->whereNull('recordatorio_enviado_at')
            ->whereDate('fecha_vencimiento', $en3dias)
            ->get();

        $encolados = 0;

        foreach ($cuotas as $cuota) {
            $acreditado = $cuota->credito?->acreditado;
            if (!$acreditado || !$acreditado->correo) continue;

            // Misma reserva que el botón, para que entre los dos no se duplique
            $reservada = DB::table('amortizaciones')
                ->where('id', $cuota->id)
                ->whereNull('recordatorio_enviado_at')
                ->update(['recordatorio_enviado_at' => now()]);

            if ($reservada === 0) continue;

            // Lo manda la cola, igual que el botón: con reintentos y, si se agotan,
            // la tarea procesar-cola falla y queda en la bitácora (ver routes/console.php)
            EnviarRecordatorioPago::dispatch($cuota);
            $encolados++;
        }

        $this->info("Recordatorios encolados: {$encolados}");
    }
}
