<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Últimos accesos del ciudadano a su cuenta. Solo lectura: los datos salen
 * siempre del usuario en sesión, nunca de un id recibido en la petición.
 */
class AccesosController extends Controller
{
    private const LIMITE = 20;

    public function index(): Response
    {
        $accesos = auth()->user()->accesos()
            ->latest('created_at')
            ->latest('id')
            ->limit(self::LIMITE)
            ->get()
            ->map(fn ($a) => [
                'id'         => $a->id,
                'fecha'      => $a->created_at->timezone('America/Merida')->format('d/m/Y'),
                'hora'       => $a->created_at->timezone('America/Merida')->format('H:i:s'),
                'ip_address' => $a->ip_address,
                'dispositivo' => $a->user_agent,
            ]);

        return Inertia::render('portal/Accesos', [
            'accesos' => $accesos,
            'limite'  => self::LIMITE,
        ]);
    }
}
