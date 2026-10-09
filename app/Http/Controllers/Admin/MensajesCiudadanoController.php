<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MensajeCiudadano;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MensajesCiudadanoController extends Controller
{
    private const TZ = 'America/Merida';

    public function index(Request $request)
    {
        $estado = $request->input('estado');

        $query = MensajeCiudadano::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($estado === 'pendientes') {
            $query->pendientes();
        } elseif ($estado === 'atendidos') {
            $query->atendidos();
        }

        $mensajes = $query->paginate(25)->withQueryString()->through(fn ($m) => [
            'id'        => $m->id,
            'fecha'     => $m->created_at->timezone(self::TZ)->format('d/m/Y H:i'),
            'ciudadano' => $m->user?->name,
            'correo'    => $m->user?->email,
            'asunto'    => $m->asunto,
            'atendido'  => $m->atendido_at !== null,
        ]);

        return Inertia::render('Admin/MensajesCiudadano/Index', [
            'mensajes'   => $mensajes,
            'filters'    => ['estado' => in_array($estado, ['pendientes', 'atendidos'], true) ? $estado : null],
            'pendientes' => MensajeCiudadano::pendientes()->count(),
        ]);
    }

    public function show(MensajeCiudadano $mensaje)
    {
        $mensaje->load('user:id,name,email', 'atendidoPor:id,name');

        return Inertia::render('Admin/MensajesCiudadano/Show', [
            'mensaje' => [
                'id'           => $mensaje->id,
                'fecha'        => $mensaje->created_at->timezone(self::TZ)->format('d/m/Y H:i'),
                'ciudadano'    => $mensaje->user?->name,
                'correo'       => $mensaje->user?->email,
                'asunto'       => $mensaje->asunto,
                'mensaje'      => $mensaje->mensaje,
                'atendido'     => $mensaje->atendido_at !== null,
                'atendido_at'  => $mensaje->atendido_at?->timezone(self::TZ)->format('d/m/Y H:i'),
                'atendido_por' => $mensaje->atendidoPor?->name,
            ],
        ]);
    }

    public function marcarAtendido(Request $request, MensajeCiudadano $mensaje)
    {
        // Idempotente: si ya estaba atendido se conserva quién y cuándo lo hizo primero.
        if ($mensaje->atendido_at === null) {
            $mensaje->update([
                'atendido_at'  => now(),
                'atendido_por' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Mensaje marcado como atendido.');
    }
}
