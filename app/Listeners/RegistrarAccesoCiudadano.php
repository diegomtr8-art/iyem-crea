<?php

namespace App\Listeners;

use App\Models\AccesoCiudadano;
use Illuminate\Auth\Events\Login;

/**
 * Anota cada inicio de sesión exitoso de un ciudadano. Solo escucha el evento
 * Login; no altera el flujo de autenticación.
 */
class RegistrarAccesoCiudadano
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! method_exists($user, 'esCiudadano') || ! $user->esCiudadano()) {
            return;
        }

        AccesoCiudadano::create([
            'user_id' => $user->getAuthIdentifier(),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
        ]);
    }
}
