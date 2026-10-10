<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega cabeceras de seguridad a todas las respuestas del grupo "web".
 *
 * - X-Frame-Options: evita que otro sitio meta CREA dentro de un iframe.
 * - X-Content-Type-Options: el navegador no "adivina" el tipo de archivo.
 * - Referrer-Policy: no se envía la URL completa a otros sitios.
 * - Strict-Transport-Security (HSTS): solo se envía cuando la petición ya
 *   llegó por HTTPS (detrás del proxy de Hostinger, trustProxies lo detecta).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
