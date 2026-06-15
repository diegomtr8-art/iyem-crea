<?php

namespace App\Http\Controllers;

use App\Mail\ContactoMensaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactoController extends Controller
{
    public function enviar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre'  => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'asunto'  => ['required', 'string', 'max:255'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        Mail::to('crea@iyemyucatan.com')
            ->send(new ContactoMensaje($data));

        return back()->with('success', 'Tu mensaje fue enviado correctamente. Te responderemos pronto a ' . $data['email'] . '.');
    }
}
