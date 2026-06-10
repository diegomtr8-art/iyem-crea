<?php

namespace App\Notifications;

use App\Models\Amortizacion;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordatorioCuota extends Notification
{
    use Queueable;

    public function __construct(private readonly Amortizacion $cuota) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $credito     = $this->cuota->credito;
        $vencimiento = Carbon::parse($this->cuota->fecha_vencimiento)->format('d/m/Y');
        $monto       = number_format((float)$this->cuota->pago_restante, 2);

        return (new MailMessage)
            ->subject("Recordatorio: Cuota #{$this->cuota->numero_cuota} vence el {$vencimiento}")
            ->greeting("Estimado(a) {$notifiable->nombre_completo},")
            ->line("Le recordamos que su cuota **#{$this->cuota->numero_cuota}** del crédito **{$credito?->clave_contrato}** vence el **{$vencimiento}**.")
            ->line("**Monto a pagar:** \${$monto} MXN")
            ->line("Puede realizar su pago en las oficinas del Instituto Yucateco de Emprendedores (IYEM) o mediante BBVA CIE con Convenio **001776533**.")
            ->action('Consultar mi crédito', url('/consultar-credito'))
            ->line('Si ya realizó su pago, por favor ignore este mensaje.')
            ->salutation('Atentamente, IYEM — Instituto Yucateco de Emprendedores');
    }
}
