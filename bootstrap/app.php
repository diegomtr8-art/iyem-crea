<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'ciudadano'  => \App\Http\Middleware\EsCiudadano::class,
            'operativo'  => \App\Http\Middleware\EsOperativo::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        // Un usuario ya autenticado que llega a una ruta de "guest" (login,
        // registro, recuperar contraseña...) se manda a SU panel según tipo,
        // en vez de siempre a /dashboard (que es exclusivo de operativos y
        // rebota a los ciudadanos de vuelta a /mi-portal).
        $middleware->redirectUsersTo(function ($request) {
            if ($request->routeIs('password.request', 'password.email', 'password.reset')) {
                $request->session()->flash('info', 'Ya tienes una sesión activa. Para cambiar tu contraseña, ve a tu perfil.');
            }

            return $request->user()?->tipo === 'ciudadano'
                ? route('portal.dashboard')
                : route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Errores del portal ciudadano: pantalla en español en vez de la página
        // técnica. Solo mi-portal/*; el panel operativo conserva sus páginas.
        // Las peticiones JSON (axios/fetch) conservan su respuesta, y el 419 de
        // navegación Inertia lo sigue manejando app.ts (recarga). Con
        // APP_DEBUG el 500 se deja a la página de depuración.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if (! $request->is('mi-portal', 'mi-portal/*')
                || ! in_array($status, [403, 404, 419, 500], true)
                || $request->expectsJson()
                || ($status === 419 && $request->header('X-Inertia'))
                || ($status === 500 && config('app.debug'))) {
                return $response;
            }

            return Inertia::render('portal/Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();

// Algunos hosts compartidos (Hostinger) no permiten fijar el document root en
// public/, así que ahí el front controller vive aplanado en la raíz de la app
// (ver index.php de producción). Detectarlo por la carpeta del front controller
// y ajustar public_path() para que coincida con lo que realmente sirve el
// servidor web; en local (docroot = public/) y en CLI no aplica.
if (PHP_SAPI !== 'cli' && isset($_SERVER['SCRIPT_FILENAME'])
    && basename(dirname($_SERVER['SCRIPT_FILENAME'])) !== 'public') {
    $app->usePublicPath($app->basePath());
}

return $app;
