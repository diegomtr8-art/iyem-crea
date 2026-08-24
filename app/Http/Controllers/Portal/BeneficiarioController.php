<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AnuncioCiudadano;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BeneficiarioController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $solicitud = $user->solicitudCredito()
            ->with(['documentos', 'modalidad'])
            ->first();

        $anuncios = AnuncioCiudadano::paraUsuario($user->id)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'titulo'     => $a->titulo,
                'mensaje'    => $a->mensaje,
                'tipo'       => $a->tipo,
                'leido'      => $a->leido,
                'url_accion' => $a->url_accion,
                'fecha'      => $a->created_at->format('d/m/Y H:i'),
            ]);

        $creditoActivo = null;
        if ($solicitud && $solicitud->credito_id) {
            $creditoActivo = [
                'id'             => $solicitud->credito_id,
                'estatus'        => $solicitud->credito->estatus ?? null,
                'monto_otorgado' => $solicitud->credito->monto_otorgado ?? null,
            ];
        }

        return Inertia::render('portal/Dashboard', [
            'solicitud'     => $solicitud ? [
                'id'          => $solicitud->id,
                'estatus'     => $solicitud->estatus,
                'observaciones' => $solicitud->observaciones,
                'nombre_completo' => $solicitud->nombre_completo,
                'credito_id'  => $solicitud->credito_id,
                'modalidad'   => $solicitud->modalidad?->nombre,
                'docs_pendientes' => $solicitud->documentos->where('estatus', 'Pendiente')->count(),
                'docs_rechazados' => $solicitud->documentos->where('estatus', 'Rechazado')->count(),
            ] : null,
            'anuncios'      => $anuncios,
            'credito_activo'=> $creditoActivo,
            'no_leidos'     => $anuncios->where('leido', false)->count(),
        ]);
    }

    public function marcarLeido(AnuncioCiudadano $anuncio): RedirectResponse|JsonResponse
    {
        if ($anuncio->user_id && $anuncio->user_id !== auth()->id()) {
            abort(403);
        }

        $anuncio->update(['leido' => true]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'no_leidos' => AnuncioCiudadano::paraUsuario(auth()->id())->where('leido', false)->count()]);
        }

        return back();
    }

    public function marcarTodosLeidos(): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        AnuncioCiudadano::paraUsuario($user->id)
            ->where('leido', false)
            ->update(['leido' => true]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'no_leidos' => 0]);
        }

        return back()->with('success', 'Todos los anuncios marcados como leídos.');
    }

    public function notificaciones(): JsonResponse
    {
        $user = auth()->user();

        $items = AnuncioCiudadano::paraUsuario($user->id)
            ->latest()
            ->take(15)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'titulo'     => $a->titulo,
                'mensaje'    => $a->mensaje,
                'tipo'       => $a->tipo,
                'leido'      => $a->leido,
                'url_accion' => $a->url_accion,
                'fecha'      => $a->created_at->diffForHumans(),
            ]);

        $noLeidos = $items->where('leido', false)->count();

        return response()->json(['items' => $items, 'no_leidos' => $noLeidos]);
    }
}
