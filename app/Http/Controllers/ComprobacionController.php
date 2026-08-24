<?php

namespace App\Http\Controllers;

use App\Models\AnuncioCiudadano;
use App\Models\AuditoriaLog;
use App\Models\ComprobacionUso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ComprobacionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ComprobacionUso::with(['credito.modalidad', 'acreditado']);

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        if ($request->input('filtro') === 'vencidas') {
            $query->where('estatus', 'Pendiente')->whereDate('fecha_limite_comprobacion', '<', now());
        } elseif ($request->input('filtro') === 'proximas') {
            $query->where('estatus', 'Pendiente')
                ->whereDate('fecha_limite_comprobacion', '>=', now())
                ->whereDate('fecha_limite_comprobacion', '<=', now()->addDays(7));
        }

        $comprobaciones = $query->orderBy('fecha_limite_comprobacion')->paginate(20)->withQueryString();

        return Inertia::render('Comprobaciones/Index', [
            'comprobaciones' => $comprobaciones->through(fn (ComprobacionUso $c) => [
                'id'                => $c->id,
                'acreditado'        => $c->acreditado?->nombre_completo,
                'clave_contrato'    => $c->credito?->clave_contrato,
                'modalidad'         => $c->credito?->modalidad?->nombre,
                'fecha_desembolso'  => $c->fecha_desembolso?->format('d/m/Y'),
                'fecha_limite'      => $c->fecha_limite_comprobacion?->format('d/m/Y'),
                'dias_restantes'    => $c->diasRestantes(),
                'estatus'           => $c->estatus,
                'semaforo'          => $c->semaforo(),
            ]),
            'filters' => $request->only(['estatus', 'filtro']),
            'kpis' => [
                'pendientes' => ComprobacionUso::where('estatus', 'Pendiente')->count(),
                'vencidas'   => ComprobacionUso::where('estatus', 'Pendiente')->whereDate('fecha_limite_comprobacion', '<', now())->count(),
                'en_revision'=> ComprobacionUso::where('estatus', 'En_Revision')->count(),
                'aprobadas'  => ComprobacionUso::where('estatus', 'Aprobada')->count(),
            ],
        ]);
    }

    public function show(ComprobacionUso $comprobacion): Response
    {
        $comprobacion->load(['credito.modalidad', 'acreditado', 'documentos', 'revisadoPor']);

        return Inertia::render('Comprobaciones/Show', [
            'comprobacion' => [
                'id'                        => $comprobacion->id,
                'estatus'                   => $comprobacion->estatus,
                'fecha_desembolso'          => $comprobacion->fecha_desembolso?->format('d/m/Y'),
                'fecha_limite_comprobacion' => $comprobacion->fecha_limite_comprobacion?->format('d/m/Y'),
                'dias_restantes'            => $comprobacion->diasRestantes(),
                'semaforo'                  => $comprobacion->semaforo(),
                'observaciones_acreditado'  => $comprobacion->observaciones_acreditado,
                'observaciones_operativo'   => $comprobacion->observaciones_operativo,
                'monto_comprobado'          => $comprobacion->monto_comprobado,
                'fecha_revision'            => $comprobacion->fecha_revision?->format('d/m/Y'),
                'revisado_por'              => $comprobacion->revisadoPor?->name,
                'credito' => [
                    'clave_contrato'  => $comprobacion->credito?->clave_contrato,
                    'monto_otorgado'  => $comprobacion->credito?->monto_otorgado,
                    'modalidad'       => $comprobacion->credito?->modalidad?->nombre,
                ],
                'acreditado' => [
                    'nombre_completo' => $comprobacion->acreditado?->nombre_completo,
                    'municipio'       => $comprobacion->acreditado?->municipio,
                ],
                'documentos' => $comprobacion->documentos->map(fn ($d) => [
                    'id'              => $d->id,
                    'tipo'            => $d->tipo,
                    'descripcion'     => $d->descripcion,
                    'monto'           => $d->monto,
                    'proveedor'       => $d->proveedor,
                    'nombre_original' => $d->nombre_original,
                    'url'             => $d->url,
                ]),
                'total_comprobado' => $comprobacion->documentos->sum('monto'),
            ],
        ]);
    }

    public function aprobar(Request $request, ComprobacionUso $comprobacion): RedirectResponse
    {
        $data = $request->validate([
            'observaciones_operativo' => 'nullable|string|max:1000',
            'monto_comprobado'        => 'required|numeric|min:0',
        ]);

        $comprobacion->update([
            'estatus'                 => 'Aprobada',
            'monto_comprobado'        => $data['monto_comprobado'],
            'observaciones_operativo' => $data['observaciones_operativo'] ?? null,
            'fecha_revision'          => now(),
            'revisado_por'            => auth()->id(),
        ]);

        AuditoriaLog::registrar('ComprobacionUso', $comprobacion->id, 'aprobada', null, null, null,
            "Comprobación de uso aprobada para el crédito {$comprobacion->credito?->clave_contrato}.");

        $userId = $comprobacion->acreditado->solicitud?->user_id;
        if ($userId) {
            AnuncioCiudadano::create([
                'user_id' => $userId,
                'titulo'  => 'Comprobación de uso aprobada',
                'mensaje' => 'Tu comprobación de uso del crédito fue revisada y aprobada. ¡Gracias!',
                'tipo'    => 'exito',
                'url_accion' => route('portal.credito'),
            ]);
        }

        return back()->with('success', 'Comprobación aprobada correctamente.');
    }

    public function rechazar(Request $request, ComprobacionUso $comprobacion): RedirectResponse
    {
        $data = $request->validate([
            'observaciones_operativo' => 'required|string|max:1000',
        ]);

        $comprobacion->update([
            'estatus'                 => 'Rechazada',
            'observaciones_operativo' => $data['observaciones_operativo'],
            'fecha_revision'          => now(),
            'revisado_por'            => auth()->id(),
        ]);

        AuditoriaLog::registrar('ComprobacionUso', $comprobacion->id, 'rechazada', null, null, null,
            "Comprobación de uso rechazada para el crédito {$comprobacion->credito?->clave_contrato}: {$data['observaciones_operativo']}");

        $userId = $comprobacion->acreditado->solicitud?->user_id;
        if ($userId) {
            AnuncioCiudadano::create([
                'user_id' => $userId,
                'titulo'  => 'Comprobación de uso rechazada',
                'mensaje' => "Tu comprobación de uso fue rechazada: {$data['observaciones_operativo']}. Por favor corrige y vuelve a enviarla.",
                'tipo'    => 'alerta',
                'url_accion' => route('portal.credito'),
            ]);
        }

        return back()->with('success', 'Comprobación rechazada. Se notificó al acreditado.');
    }
}
