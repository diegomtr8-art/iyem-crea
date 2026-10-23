<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\ComprobacionUso;
use App\Models\Credito;
use App\Models\Desembolso;
use App\Models\Pago;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    // created_at se guarda en UTC; los filtros y la pantalla van en hora de Mérida
    private const ZONA = 'America/Merida';

    public function index(Request $request): Response
    {
        $query = AuditoriaLog::with('usuario')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

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
            $query->where('created_at', '>=', Carbon::parse($request->fecha_desde, self::ZONA)->startOfDay()->utc());
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('created_at', '<=', Carbon::parse($request->fecha_hasta, self::ZONA)->endOfDay()->utc());
        }

        if ($request->filled('buscar')) {
            $this->buscar($query, trim($request->buscar));
        }

        $porAccion = (clone $query)->reorder()->toBase()
            ->selectRaw('accion, COUNT(*) AS total')
            ->groupBy('accion')
            ->pluck('total', 'accion');

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Auditoria/Index', [
            'totales' => [
                'total'        => (int) $porAccion->sum(),
                'creados'      => (int) ($porAccion['created'] ?? 0),
                'actualizados' => (int) ($porAccion['updated'] ?? 0),
                'eliminados'   => (int) ($porAccion['deleted'] ?? 0),
            ],
            // Solo quienes aparecen en la bitácora, no todos los usuarios del sistema
            'usuarios' => User::whereIn('id', AuditoriaLog::query()->whereNotNull('usuario_id')->select('usuario_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
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
                'fecha'           => $l->created_at->timezone(self::ZONA)->format('d/m/Y H:i:s'),
            ]),
            'filters' => $request->only(['entidad', 'accion', 'usuario_id', 'fecha_desde', 'fecha_hasta', 'buscar']),
            'entidades' => AuditoriaLog::query()->distinct()->orderBy('entidad')->pluck('entidad'),
        ]);
    }

    /**
     * Busca el texto en la fila y, si es una clave de contrato, trae también las filas de ese
     * crédito, sus pagos, desembolsos y comprobaciones. Así aparecen los registros viejos, cuya
     * descripción dice "Crédito ID: N" en lugar de la clave, sin tener que reescribirlos.
     */
    private function buscar(Builder $query, string $texto): void
    {
        $creditos = Credito::query()
            ->where('clave_contrato', 'like', "%{$texto}%")
            ->orWhere('numero_contrato_oficial', 'like', "%{$texto}%")
            ->select('id');

        $relacionadas = [
            'Credito'         => $creditos,
            'Pago'            => Pago::whereIn('credito_id', $creditos)->select('id'),
            'Desembolso'      => Desembolso::whereIn('credito_id', $creditos)->select('id'),
            'ComprobacionUso' => ComprobacionUso::whereIn('credito_id', $creditos)->select('id'),
        ];

        $query->where(function ($q) use ($texto, $relacionadas) {
            $q->where('descripcion', 'like', "%{$texto}%")
              ->orWhere('valor_anterior', 'like', "%{$texto}%")
              ->orWhere('valor_nuevo', 'like', "%{$texto}%")
              ->orWhere('usuario_nombre', 'like', "%{$texto}%")
              ->orWhere('ip_address', 'like', "%{$texto}%");

            foreach ($relacionadas as $entidad => $ids) {
                $q->orWhere(fn ($r) => $r->where('entidad', $entidad)->whereIn('entidad_id', $ids));
            }
        });
    }
}
