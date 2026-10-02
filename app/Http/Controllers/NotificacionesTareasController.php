<?php

namespace App\Http\Controllers;

use App\Notifications\TareaProgramadaFallida;
use Illuminate\Http\Request;

class NotificacionesTareasController extends Controller
{
    // Marca como leídas las alertas que el administrador acaba de ver al abrir el ícono
    public function marcarLeidas(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'string',
        ]);

        $request->user()->unreadNotifications()
            ->where('type', TareaProgramadaFallida::class)
            ->whereIn('id', $data['ids'])
            ->update(['read_at' => now()]);

        return back();
    }
}