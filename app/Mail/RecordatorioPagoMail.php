<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Amortizacion;

class RecordatorioPagoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly string $nombre,
        public readonly string $contrato,
        public readonly int $cuota,
        public readonly float $monto,
        public readonly string $vence,
    ) {}

    /**
     * Arma el correo a partir de una cuota. Lo usan el botón (Job) y el proceso diario,
     * así los dos mandan exactamente el mismo mensaje.
     */
    public static function desdeCuota(Amortizacion $cuota): self
    {
        return new self(
            nombre:   $cuota->credito->acreditado->nombre_completo,
            contrato: $cuota->credito->clave_contrato,
            cuota:    $cuota->numero_cuota,
            monto:    (float) $cuota->pago_restante,
            vence:    $cuota->fecha_vencimiento->format('d/m/Y'),
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio de pago: cuota {$this->cuota} vence el {$this->vence}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-pago',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
