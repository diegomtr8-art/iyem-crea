<?php

namespace App\Console\Commands;

use App\Models\Amortizacion;
use App\Mail\RecordatorioPagoMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class RecordatoriosPago extends Command
{
    // Días de anticipación del aviso. El botón del administrador usa este mismo límite.
    public const DIAS_ANTICIPACION = 3;
    protected $signature   = 'crea:recordatorios-pago';
    protected $description = 'Envía recordatorio por correo a acreditados con cuota que vence en 3 días';

    public function handle(): void
    {
        $en3dias = Carbon::now('America/Merida')->addDays(self::DIAS_ANTICIPACION)->toDateString();

        $cuotas = Amortizacion::with(['credito.acreditado'])
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->whereNull('recordatorio_enviado_at')
            ->whereDate('fecha_vencimiento', $en3dias)
            ->get();

        $enviados = 0;

        foreach ($cuotas as $cuota) {
            $acreditado = $cuota->credito?->acreditado;
            if (!$acreditado || !$acreditado->correo) continue;

            // Misma reserva que el botón, para que entre los dos no se duplique
            $reservada = DB::table('amortizaciones')
                ->where('id', $cuota->id)
                ->whereNull('recordatorio_enviado_at')
                ->update(['recordatorio_enviado_at' => now()]);

            if ($reservada === 0) continue;

            try {
                Mail::to($acreditado->correo)->send(RecordatorioPagoMail::desdeCuota($cuota));
                $enviados++;
            } catch (\Exception $e) {
                DB::table('amortizaciones')->where('id', $cuota->id)->update(['recordatorio_enviado_at' => null]);
                $this->warn("No se pudo notificar a {$acreditado->nombre_completo}: {$e->getMessage()}");
            }
        }

        $this->info("Recordatorios enviados: {$enviados}");
    }
}
