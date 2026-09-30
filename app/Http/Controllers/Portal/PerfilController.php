<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'correo'   => 'required|email:rfc|max:255',
        ], [
            'telefono.required' => 'El teléfono celular es obligatorio.',
            'telefono.digits'   => 'El teléfono celular debe tener 10 dígitos.',
            'correo.required'   => 'El correo electrónico es obligatorio.',
        ]);

        DB::transaction(function () use ($solicitud, $datos) {
            $solicitud->update([
                'telefono' => $datos['telefono'],
                'correo'   => $datos['correo'],
            ]);

            // Los recordatorios de pago y cobranza leen el correo del acreditado.
            $solicitud->acreditado?->update(['correo' => $datos['correo']]);
        });

        return redirect()->route('portal.perfil')
            ->with('success', 'Tus datos de contacto se guardaron correctamente.');
    }
}
