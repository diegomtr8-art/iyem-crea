<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerfilController extends Controller
{
    public function edit(): Response|RedirectResponse
    {
        $solicitud = auth()->user()->solicitudCredito;

        if (!$solicitud) {
            return redirect()->route('portal.solicitud.index')
                ->with('info', 'Aún no tienes una solicitud registrada. Inicia tu solicitud para poder editar tus datos de contacto.');
        }

        return Inertia::render('portal/Perfil', [
            'perfil' => [
                'telefono' => $solicitud->telefono,
                'correo'   => $solicitud->correo,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $solicitud = auth()->user()->solicitudCredito;

        if (!$solicitud) {
            return redirect()->route('portal.solicitud.index')
                ->with('info', 'Aún no tienes una solicitud registrada. Inicia tu solicitud para poder editar tus datos de contacto.');
        }

        $datos = $request->validate([
            'telefono' => 'required|digits:10',
        ], [
            'telefono.required' => 'El teléfono celular es obligatorio.',
            'telefono.digits'   => 'El teléfono celular debe tener 10 dígitos.',
        ]);

        // El correo no se edita desde el portal: los recordatorios de pago y cobranza
        // lo leen del acreditado y no debe poder desviarse con una sesión ajena.
        $solicitud->update(['telefono' => $datos['telefono']]);

        return redirect()->route('portal.perfil')
            ->with('success', 'Tu teléfono se guardó correctamente.');
    }
}
