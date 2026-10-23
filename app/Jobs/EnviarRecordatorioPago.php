<?php

namespace App\Jobs;

use App\Mail\RecordatorioPagoMail;
use App\Models\Amortizacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarRecordatorioPago implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    // Espera entre intentos (1 y 5 minutos), por si el servidor de correo falla un rato
    public array $backoff = [60, 300];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public Amortizacion $cuota) {}

    public function handle(): void
    {
        $cuota = Amortizacion::with('credito.acreditado')->find($this->cuota->id);

        // Si entre el clic y el envío la cuota se pagó o cambió de estado, no se avisa
        if (! $cuota || in_array($cuota->estado, ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'], true)) {
            return;
        }

        $acreditado = $cuota->credito?->acreditado;
        if (! $acreditado?->correo) {
            return;
        }

        Mail::to($acreditado->correo)->send(RecordatorioPagoMail::desdeCuota($cuota));
    }

    // Si falló después de los 3 intentos, se quita la marca para poder reenviarlo con el botón.
    // La tarea procesar-cola ve el fallo y lo deja en la bitácora (ver routes/console.php).
    public function failed(Throwable $e): void
    {
        DB::table('amortizaciones')->where('id', $this->cuota->id)
            ->update(['recordatorio_enviado_at' => null]);

        Log::error('No se pudo enviar un recordatorio de pago después de 3 intentos', [
            'cuota_id' => $this->cuota->id,
            'error'    => $e->getMessage(),
        ]);
    }
}