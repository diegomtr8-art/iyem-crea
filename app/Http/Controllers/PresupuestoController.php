<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\ModalidadCrea;
use App\Models\PresupuestoPrograma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PresupuestoController extends Controller
{
    public function index(): Response
    {
        $anioActual = now()->year;
        $modalidades = ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        $presupuestos = PresupuestoPrograma::with(['modalidad', 'registradoPor'])
            ->where('ejercicio_fiscal', $anioActual)
            ->get();

        $ejercidos = Credito::where('estatus', '!=', 'Cancelado')
            ->whereYear('fecha_entrega', $anioActual)
            ->join('modalidad_creas', 'creditos.modalidad_id', '=', 'modalidad_creas.id')
            ->selectRaw('creditos.modalidad_id, SUM(creditos.monto_otorgado) as total_ejercido')
            ->groupBy('creditos.modalidad_id')
            ->pluck('total_ejercido', 'modalidad_id');

        $resumen = $modalidades->map(function ($m) use ($presupuestos, $ejercidos, $anioActual) {
            $pres = $presupuestos->firstWhere('modalidad_id', $m->id);
            $ejercido = (float) ($ejercidos[$m->id] ?? 0);
            $autorizado = $pres ? (float) $pres->monto_autorizado : 0;
            return [
                'modalidad_id'    => $m->id,
                'modalidad_nombre'=> $m->nombre,
                'monto_autorizado'=> $autorizado,
                'monto_ejercido'  => $ejercido,
                'monto_disponible'=> max(0, $autorizado - $ejercido),
                'porcentaje_uso'  => $autorizado > 0 ? round(($ejercido / $autorizado) * 100, 1) : 0,
            ];
        });

        return Inertia::render('Presupuesto/Index', [
            'resumen'       => $resumen,
            'modalidades'   => $modalidades,
            'anio_actual'   => $anioActual,
            'presupuestos'  => $presupuestos->map(fn($p) => [
                'id'              => $p->id,
                'ejercicio_fiscal'=> $p->ejercicio_fiscal,
                'modalidad_id'    => $p->modalidad_id,
                'modalidad'       => $p->modalidad?->nombre ?? 'General',
                'monto_autorizado'=> $p->monto_autorizado,
                'observaciones'   => $p->observaciones,
                'registrado_por'  => $p->registradoPor?->name,
                'created_at'      => $p->created_at->format('d/m/Y'),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ejercicio_fiscal' => 'required|integer|min:2020|max:2100',
            'modalidad_id'     => 'nullable|exists:modalidad_creas,id',
            'monto_autorizado' => 'required|numeric|min:1',
            'observaciones'    => 'nullable|string|max:500',
        ]);

        PresupuestoPrograma::updateOrCreate(
            [
                'ejercicio_fiscal' => $data['ejercicio_fiscal'],
                'modalidad_id'     => $data['modalidad_id'] ?? null,
            ],
            [
                'monto_autorizado' => $data['monto_autorizado'],
                'observaciones'    => $data['observaciones'] ?? null,
                'registrado_por'   => auth()->id(),
            ]
        );

        return back()->with('success', 'Presupuesto guardado correctamente.');
    }
}
