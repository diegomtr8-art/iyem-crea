<?php

namespace App\Mail;

use App\Models\MensajeCiudadano;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MensajeCiudadanoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly MensajeCiudadano $mensaje) {}

    public function envelope(): Envelope
    {
        $user = $this->mensaje->user;

        return new Envelope(
            subject: '[CREA Portal] ' . $this->mensaje->asunto,
            replyTo: $user ? [new Address($user->email, $user->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.mensaje-ciudadano');
    }
}
