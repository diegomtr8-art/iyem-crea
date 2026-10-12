<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra el portal del ciudadano cuando PORTAL_ABIERTO=false: sus rutas
 * responden 404 como si no existieran. No bloquea /, /login, /logout,
 * recuperar contraseña ni nada del panel del personal.
 */
class PortalCerrado
{
    private const RUTAS_PORTAL = [
        'mi-portal', 'mi-portal/*',
        'solicitud', 'solicitud/*',
        'ciudadano/registro',
        'auth/google/*',
        'consultar-credito',
        'contacto',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('portal.abierto') && $request->is(...self::RUTAS_PORTAL)) {
            abort(404);
        }

        return $next($request);
    }
}
