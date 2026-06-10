<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditoriaLog::with('usuario')
            ->orderByDesc('created_at');

        if ($request->filled('entidad')) {
            $query->where('entidad', $request->entidad);
        }

        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('descripcion', 'like', "%{$b}%")
                  ->orWhere('valor_anterior', 'like', "%{$b}%")
                  ->orWhere('valor_nuevo', 'like', "%{$b}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Auditoria/Index', [
            'logs' => $logs->through(fn($l) => [
                'id'              => $l->id,
                'entidad'         => $l->entidad,
                'entidad_id'      => $l->entidad_id,
                'accion'          => $l->accion,
                'campo_modificado'=> $l->campo_modificado,
                'valor_anterior'  => $l->valor_anterior,
                'valor_nuevo'     => $l->valor_nuevo,
                'usuario'         => $l->usuario_nombre ?? $l->usuario?->name,
                'ip_address'      => $l->ip_address,
                'descripcion'     => $l->descripcion,
                'fecha'           => $l->created_at->format('d/m/Y H:i:s'),
            ]),
            'filters' => $request->only(['entidad', 'accion', 'usuario_id', 'fecha_desde', 'fecha_hasta', 'buscar']),
            'entidades' => ['Credito', 'Pago', 'SolicitudCredito', 'ExpedienteJuridico', 'Desembolso'],
        ]);
    }
}
