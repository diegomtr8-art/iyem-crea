<?php

namespace App\Providers;

use App\Models\Credito;
use App\Models\ExpedienteJuridico;
use App\Models\Pago;
use App\Observers\CreditoObserver;
use App\Observers\ExpedienteJuridicoObserver;
use App\Observers\PagoObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Credito::observe(CreditoObserver::class);
        Pago::observe(PagoObserver::class);
        ExpedienteJuridico::observe(ExpedienteJuridicoObserver::class);

        $this->customizePasswordResetEmail();
        $this->customizeVerifyEmail();
    }

    private function customizePasswordResetEmail(): void
    {
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

            $email = method_exists($notifiable, 'getEmailForPasswordReset')
                ? $notifiable->getEmailForPasswordReset()
                : ($notifiable->email ?? '');

            // El dominio sale de APP_URL, no de la petición (que el visitante puede manipular).
            $url = rtrim(config('app.url'), '/') . route('password.reset', [
                'token' => $token,
                'email' => $email,
            ], false);

            return (new MailMessage)
                ->subject('Recupera tu contraseña — CREA')
                ->greeting($this->saludo($notifiable, '¡Hola, :nombre!'))
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta en el sistema CREA.')
                ->line('Haz clic en el botón para crear una nueva contraseña. Si no fuiste tú, puedes ignorar este correo.')
                ->action('Restablecer contraseña', $url)
                ->line("Este enlace expirará en {$expire} minutos.")
                ->line('Si no solicitaste este cambio, tu contraseña permanecerá sin modificaciones.')
                ->salutation('Equipo CREA — Instituto Yucateco de Emprendedores');
        });
    }

    private function customizeVerifyEmail(): void
    {
        // Mismo enlace firmado que genera Laravel, pero con el dominio de APP_URL.
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // El esquema (http/https) también sale de APP_URL; si no, Laravel usa el de la petición.
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme(parse_url(config('app.url'), PHP_URL_SCHEME) ?: null);

            try {
                return URL::temporarySignedRoute(
                    'verification.verify',
                    Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
                    [
                        'id'   => $notifiable->getKey(),
                        'hash' => sha1($notifiable->getEmailForVerification()),
                    ]
                );
            } finally {
                URL::forceRootUrl(null);
                URL::forceScheme(null);
            }
        });

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verifica tu correo electrónico — CREA')
                ->greeting($this->saludo($notifiable, '¡Bienvenido a CREA, :nombre!'))
                ->line('Gracias por registrarte. Por favor confirma tu correo electrónico haciendo clic en el siguiente botón.')
                ->action('Verificar correo electrónico', $url)
                ->line('Si no creaste esta cuenta, no es necesario que hagas nada.')
                ->salutation('Equipo CREA — Instituto Yucateco de Emprendedores');
        });
    }

    /** Saludo con el nombre del usuario; sin nombre, queda el saludo genérico. */
    private function saludo($notifiable, string $plantilla): string
    {
        $nombre = trim((string) ($notifiable->name ?? ''));

        return $nombre === ''
            ? str_replace(', :nombre', '', $plantilla)
            : str_replace(':nombre', $nombre, $plantilla);
    }
}
