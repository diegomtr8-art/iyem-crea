<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\MensajeCiudadanoMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class MensajeContactoController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('portal/Contacto');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asunto'  => 'required|string|max:255',
            'mensaje' => 'required|string|max:2000',
        ]);

        // El ciudadano sale de la sesión, nunca del request.
        $mensaje = $request->user()->mensajes()->create($data);

        // El aviso al equipo es secundario: si el correo falla, el mensaje ya quedó guardado.
        try {
            Mail::to('crea@iyemyucatan.com')->send(new MensajeCiudadanoMail($mensaje));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el aviso de mensaje de ciudadano', [
                'mensaje_id' => $mensaje->id,
                'error'      => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Tu mensaje fue enviado. El equipo de IYEM te responderá pronto.');
    }
}
