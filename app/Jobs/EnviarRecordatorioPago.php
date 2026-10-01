<?php

namespace App\Jobs;

use App\Mail\RecordatorioPagoMail;
use App\Models\Amortizacion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarRecordatorioPago implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
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

        Mail::to($acreditado->correo)->send(new RecordatorioPagoMail(
            nombre:   $acreditado->nombre_completo,
            contrato: $cuota->credito->clave_contrato,
            cuota:    $cuota->numero_cuota,
            monto:    (float) $cuota->pago_restante,
            vence:    $cuota->fecha_vencimiento->format('d/m/Y'),
        ));
    }

    // Si falló después de los 3 intentos, se quita la marca para poder reintentar después
    public function failed(Throwable $e): void
    {
        DB::table('amortizaciones')->where('id', $this->cuota->id)
            ->update(['recordatorio_enviado_at' => null]);
    }
}