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

            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $email,
            ], false));

            return (new MailMessage)
                ->subject('Recupera tu contraseña — CREA')
                ->greeting('¡Hola!')
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
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verifica tu correo electrónico — CREA')
                ->greeting('¡Bienvenido a CREA!')
                ->line('Gracias por registrarte. Por favor confirma tu correo electrónico haciendo clic en el siguiente botón.')
                ->action('Verificar correo electrónico', $url)
                ->line('Si no creaste esta cuenta, no es necesario que hagas nada.')
                ->salutation('Equipo CREA — Instituto Yucateco de Emprendedores');
        });
    }
}
