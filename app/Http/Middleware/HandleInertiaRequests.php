<?php

namespace App\Http\Middleware;

use App\Models\AnuncioCiudadano;
use App\Models\ComprobacionUso;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            
            // --- DATOS DE AUTENTICACIÓN Y PERMISOS ---
            'auth' => [
                'user' => $request->user() ? [
                    'id'          => $request->user()->id,
                    'name'        => $request->user()->name,
                    'email'       => $request->user()->email,
                    'tipo'        => $request->user()->tipo,
                    // Solo carga permisos para operativos (evita overhead en ciudadanos)
                    'roles'       => $request->user()->tipo === 'operativo'
                        ? $request->user()->getRoleNames()
                        : collect([]),
                    'permissions' => $request->user()->tipo === 'operativo'
                        ? $request->user()->getAllPermissions()->pluck('name')
                        : collect([]),
                ] : null,
            ],

            // --- ESTADO DEL PORTAL CIUDADANO ---
            'portal' => ($request->user() && $request->user()->tipo === 'ciudadano') ? (function() use ($request) {
                $solicitud = $request->user()->solicitudCredito;
                return [
                    'tiene_credito'   => (bool) $solicitud?->credito_id,
                    'tiene_solicitud' => (bool) $solicitud,
                    'estatus_solicitud'=> $solicitud?->estatus,
                    'no_leidos'       => AnuncioCiudadano::paraUsuario($request->user()->id)->where('leido', false)->count(),
                ];
            })() : null,

            // --- BADGES OPERATIVOS ---
            'comprobaciones_pendientes' => ($request->user() && $request->user()->tipo === 'operativo')
                ? ComprobacionUso::where('estatus', 'Pendiente')
                    ->whereDate('fecha_limite_comprobacion', '<=', now()->addDays(7))
                    ->count()
                : null,

            // --- NOTIFICACIONES FLASH ---
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
        ]);
    }
}