<?php

namespace App\Http\Controllers;

use App\Services\CorreosFallidosService;
use Inertia\Inertia;

class CorreosFallidosController extends Controller
{
    public function index(CorreosFallidosService $correos)
    {
        return Inertia::render('Admin/CorreosFallidos', ['fallos' => $correos->pendientes()]);
    }

    public function reintentar(int $fallo, CorreosFallidosService $correos)
    {
        $resultado = $correos->reintentar($fallo);

        return back()->with($resultado['enviado'] ? 'success' : 'warning', $resultado['mensaje']);
    }

    public function reintentarTodos(CorreosFallidosService $correos)
    {
        $ids = $correos->ids();

        if ($ids->isEmpty()) {
            return back()->with('warning', 'No hay correos fallidos pendientes.');
        }

        $reenviados = 0;
        $noEnviados = [];

        foreach ($ids as $id) {
            $resultado = $correos->reintentar($id);

            if ($resultado['enviado']) {
                $reenviados++;
            } else {
                $noEnviados[] = $resultado['mensaje'];
            }
        }

        $respuesta = back()->with('success', "Se volvieron a poner en la cola de envío {$reenviados} de {$ids->count()} correo(s).");

        return $noEnviados ? $respuesta->with('warning', implode(' ', $noEnviados)) : $respuesta;
    }

    public function descartar(int $fallo, CorreosFallidosService $correos)
    {
        return $correos->descartar($fallo)
            ? back()->with('success', 'Se descartó el correo. No se envió nada.')
            : back()->with('warning', 'Ese correo ya no estaba en la lista: alguien más ya lo reintentó o lo descartó.');
    }
}
