<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ModalidadCrea;

class DashboardController extends Controller
{
    public function index()
    {
        $hoy        = Carbon::now('America/Merida')->toDateString();
        $modId      = request('modalidad_id') ? (int) request('modalidad_id') : null;
        $modalidades = ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        // ── Helpers para filtrar por modalidad ───────────────────────────────
        $credQ = fn() => DB::table('creditos')->when($modId, fn($q) => $q->where('modalidad_id', $modId));

        $pagosQ = fn() => DB::table('pagos')
            ->where('pagos.cancelado', false)
            ->when($modId, fn($q) => $q->join('creditos as c_fil', 'pagos.credito_id', '=', 'c_fil.id')
                ->where('c_fil.modalidad_id', $modId));

        $amorQ = fn() => DB::table('amortizaciones')
            ->when($modId, fn($q) => $q->join('creditos as c_fil', 'amortizaciones.credito_id', '=', 'c_fil.id')
                ->where('c_fil.modalidad_id', $modId));

        // ── Capital ──────────────────────────────────────────────────────────
        $colocado   = (float) $credQ()->sum('monto_otorgado');
        $recuperado = (float) $pagosQ()->sum('pagos.aplicado_capital');
        $moraPagada = (float) $pagosQ()->sum('pagos.aplicado_mora');
        $ordPagado  = (float) $pagosQ()->sum('pagos.aplicado_ordinario');

        // ── Conteos ──────────────────────────────────────────────────────────
        $creditosActivos    = $credQ()->where('estatus', 'Activo')->count();
        $creditosMorosos    = $credQ()->where('estatus', 'Moroso')->count();
        $creditosLiquidados = $credQ()->where('estatus', 'Liquidado')->count();
        $totalCreditos      = $credQ()->count();

        $totalAcreditados = $modId
            ? DB::table('acreditados')
                ->join('creditos', 'creditos.acreditado_id', '=', 'acreditados.id')
                ->where('creditos.modalidad_id', $modId)
                ->distinct()->count('acreditados.id')
            : DB::table('acreditados')->count();

        $cuotasVencidas = $amorQ()
            ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->where('amortizaciones.fecha_vencimiento', '<', $hoy)
            ->count();

        // ── Género ───────────────────────────────────────────────────────────
        $hombres = $modId
            ? DB::table('acreditados')->join('creditos', 'creditos.acreditado_id', '=', 'acreditados.id')
                ->where('creditos.modalidad_id', $modId)->where('acreditados.sexo', 'H')->distinct()->count('acreditados.id')
            : DB::table('acreditados')->where('sexo', 'H')->count();
        $mujeres = $modId
            ? DB::table('acreditados')->join('creditos', 'creditos.acreditado_id', '=', 'acreditados.id')
                ->where('creditos.modalidad_id', $modId)->where('acreditados.sexo', 'M')->distinct()->count('acreditados.id')
            : DB::table('acreditados')->where('sexo', 'M')->count();

        // ── Por modalidad ────────────────────────────────────────────────────
        $porModalidad = DB::table('creditos')
            ->join('modalidad_creas', 'creditos.modalidad_id', '=', 'modalidad_creas.id')
            ->when($modId, fn($q) => $q->where('creditos.modalidad_id', $modId))
            ->groupBy('modalidad_creas.id', 'modalidad_creas.nombre')
            ->select(
                'modalidad_creas.nombre',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(creditos.monto_otorgado) as monto')
            )
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['nombre' => $r->nombre, 'total' => (int) $r->total, 'monto' => (float) $r->monto]);

        // ── Por municipio ────────────────────────────────────────────────────
        $porMunicipio = DB::table('acreditados')
            ->leftJoin('creditos', 'creditos.acreditado_id', '=', 'acreditados.id')
            ->whereNotNull('acreditados.municipio')
            ->where('acreditados.municipio', '!=', '')
            ->when($modId, fn($q) => $q->where('creditos.modalidad_id', $modId))
            ->groupBy('acreditados.municipio')
            ->select(
                'acreditados.municipio',
                DB::raw('COUNT(DISTINCT acreditados.id) as total'),
                DB::raw('COALESCE(SUM(creditos.monto_otorgado), 0) as monto')
            )
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['municipio' => $r->municipio, 'total' => (int) $r->total, 'monto' => (float) $r->monto]);

        // ── Evolución mensual ─────────────────────────────────────────────────
        $evolucionMensual = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now('America/Merida')->subMonths($i);
            $base  = DB::table('pagos')->where('cancelado', false)
                ->whereYear('fecha_pago', $fecha->year)
                ->whereMonth('fecha_pago', $fecha->month);
            if ($modId) {
                $base = $base->join('creditos as c_ev', 'pagos.credito_id', '=', 'c_ev.id')
                    ->where('c_ev.modalidad_id', $modId);
            }
            $evolucionMensual[] = [
                'label' => ucfirst($fecha->locale('es')->isoFormat('MMM YY')),
                'pagos' => (clone $base)->count(),
                'monto' => (float) (clone $base)->sum('pagos.monto_recibido'),
            ];
        }

        // ── Cartera vencida ───────────────────────────────────────────────────
        $cartaVencidaMonto = (float) DB::table('amortizaciones')
            ->join('creditos', 'creditos.id', '=', 'amortizaciones.credito_id')
            ->where('creditos.estatus', 'Moroso')
            ->where('amortizaciones.estado', 'Pendiente')
            ->where('amortizaciones.fecha_vencimiento', '<', $hoy)
            ->when($modId, fn($q) => $q->where('creditos.modalidad_id', $modId))
            ->sum('amortizaciones.pago_restante');

        $capitalPendiente = round($colocado - $recuperado, 2);

        // ── Pagos recientes ───────────────────────────────────────────────────
        $pagosRecientes = DB::table('pagos')
            ->join('acreditados', 'pagos.acreditado_id', '=', 'acreditados.id')
            ->join('users', 'pagos.registrado_por', '=', 'users.id')
            ->join('creditos as c_pr', 'pagos.credito_id', '=', 'c_pr.id')
            ->where('pagos.cancelado', false)
            ->when($modId, fn($q) => $q->where('c_pr.modalidad_id', $modId))
            ->orderByDesc('pagos.created_at')
            ->limit(6)
            ->select(
                'pagos.folio',
                'pagos.monto_recibido',
                'pagos.fecha_pago',
                'pagos.forma_pago',
                'acreditados.nombre_completo',
                'users.name as cajero'
            )
            ->get();

        // ── Mora pendiente (calculada dinámicamente) ──────────────────────────
        $moraPendiente = (float) (DB::table('amortizaciones')
            ->join('creditos as c_mp', 'amortizaciones.credito_id', '=', 'c_mp.id')
            ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->where('amortizaciones.fecha_vencimiento', '<', $hoy)
            ->when($modId, fn($q) => $q->where('c_mp.modalidad_id', $modId))
            ->selectRaw('
                SUM(
                    CASE WHEN DATEDIFF(?, amortizaciones.fecha_vencimiento) > 5
                    THEN ROUND(
                        GREATEST(0, amortizaciones.saldo_insoluto - amortizaciones.capital_pagado)
                        * (c_mp.tasa_interes_moratorio / 100.0 / 360.0)
                        * DATEDIFF(?, amortizaciones.fecha_vencimiento),
                    2)
                    ELSE 0 END
                ) as mora_calc
            ', [$hoy, $hoy])
            ->first()
            ->mora_calc ?? 0);

        $pagosMes = DB::table('pagos')
            ->where('cancelado', false)
            ->whereYear('fecha_pago', now()->year)
            ->whereMonth('fecha_pago', now()->month)
            ->when($modId, fn($q) => $q->join('creditos as c_pm', 'pagos.credito_id', '=', 'c_pm.id')
                ->where('c_pm.modalidad_id', $modId))
            ->count();

        // ── Índice de Morosidad (IM) y recuperación ────────────────────────
        $carteraBruta  = (float) $credQ()->whereIn('estatus', ['Activo', 'Moroso'])->sum('monto_otorgado');
        $cartaVencidaIMO = (float) DB::table('amortizaciones')
            ->join('creditos as c_im', 'amortizaciones.credito_id', '=', 'c_im.id')
            ->whereIn('c_im.estatus', ['Activo', 'Moroso'])
            ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->where('amortizaciones.fecha_vencimiento', '<', $hoy)
            ->when($modId, fn($q) => $q->where('c_im.modalidad_id', $modId))
            ->sum('amortizaciones.pago_restante');

        $indiceMorosidad = $carteraBruta > 0 ? round(($cartaVencidaIMO / $carteraBruta) * 100, 2) : 0;

        $pagosMesEsperados = (float) DB::table('amortizaciones')
            ->join('creditos as c_pe', 'amortizaciones.credito_id', '=', 'c_pe.id')
            ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->whereYear('amortizaciones.fecha_vencimiento', now()->year)
            ->whereMonth('amortizaciones.fecha_vencimiento', now()->month)
            ->when($modId, fn($q) => $q->where('c_pe.modalidad_id', $modId))
            ->sum('amortizaciones.cuota_fija');

        $pagosMesRecibidos = (float) DB::table('pagos')
            ->where('cancelado', false)
            ->whereYear('fecha_pago', now()->year)
            ->whereMonth('fecha_pago', now()->month)
            ->when($modId, fn($q) => $q->join('creditos as c_pr2', 'pagos.credito_id', '=', 'c_pr2.id')
                ->where('c_pr2.modalidad_id', $modId))
            ->sum('pagos.monto_recibido');

        $indiceRecuperacion = $pagosMesEsperados > 0 ? round(($pagosMesRecibidos / $pagosMesEsperados) * 100, 1) : 0;

        // ── Alertas ──────────────────────────────────────────────────────
        $creditosCandidatosJuridico = (int) DB::table('creditos')
            ->join('amortizaciones', 'creditos.id', '=', 'amortizaciones.credito_id')
            ->whereNotIn('creditos.estatus', ['Liquidado', 'Cancelado'])
            ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->where('amortizaciones.fecha_vencimiento', '<', now()->subDays(90))
            ->whereNotExists(fn($q) => $q->from('expedientes_juridicos')->whereColumn('expedientes_juridicos.credito_id', 'creditos.id'))
            ->when($modId, fn($q) => $q->where('creditos.modalidad_id', $modId))
            ->distinct()->count('creditos.id');

        $solicitudesSinMovimiento = (int) DB::table('solicitudes_credito')
            ->whereIn('estatus', ['Enviada', 'En_Revision'])
            ->where('updated_at', '<', now()->subDays(5))
            ->count();

        // ── Semáforo de cartera ──────────────────────────────────────────
        $semaforoPorDias = function (int $desde, int $hasta = null) use ($hoy, $modId): int {
            return (int) DB::table('creditos')
                ->join('amortizaciones', 'creditos.id', '=', 'amortizaciones.credito_id')
                ->whereNotIn('creditos.estatus', ['Liquidado', 'Cancelado'])
                ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada'])
                ->where('amortizaciones.fecha_vencimiento', '<', now()->subDays($desde))
                ->when($hasta !== null, fn($q) => $q->where('amortizaciones.fecha_vencimiento', '>=', now()->subDays($hasta)))
                ->when($modId, fn($q) => $q->where('creditos.modalidad_id', $modId))
                ->distinct()->count('creditos.id');
        };

        return Inertia::render('Dashboard', [
            'modalidades'      => $modalidades,
            'modalidad_activa' => $modId,
            'stats' => [
                'capital' => [
                    'colocado'   => $colocado,
                    'recuperado' => $recuperado,
                    'pendiente'  => $capitalPendiente,
                ],
                'intereses' => [
                    'ordinario_cobrado' => $ordPagado,
                    'mora_cobrada'      => $moraPagada,
                    'mora_pendiente'    => $moraPendiente,
                ],
                'conteo' => [
                    'total'       => $totalCreditos,
                    'activos'     => $creditosActivos,
                    'morosos'     => $creditosMorosos,
                    'liquidados'  => $creditosLiquidados,
                    'acreditados' => $totalAcreditados,
                    'vencidos'    => $cuotasVencidas,
                    'pagos_mes'   => $pagosMes,
                ],
                'kpis' => [
                    'ticket_promedio'           => $totalCreditos > 0 ? round((float) $credQ()->avg('monto_otorgado'), 2) : 0,
                    'tasa_recuperacion'         => $colocado > 0 ? round(($recuperado / $colocado) * 100, 1) : 0,
                    'cartera_vencida_monto'     => $cartaVencidaMonto,
                    'cartera_vencida_pct'       => $capitalPendiente > 0 ? round(($cartaVencidaMonto / $capitalPendiente) * 100, 1) : 0,
                    'rendimiento_total'         => $colocado > 0 ? round((($ordPagado + $moraPagada) / $colocado) * 100, 2) : 0,
                    'indice_morosidad'          => $indiceMorosidad,
                    'indice_recuperacion_mes'   => $indiceRecuperacion,
                    'pagos_mes_esperados'       => round($pagosMesEsperados, 2),
                    'pagos_mes_recibidos'       => round($pagosMesRecibidos, 2),
                ],
                'alertas' => [
                    'candidatos_juridico'      => $creditosCandidatosJuridico,
                    'solicitudes_sin_movimiento'=> $solicitudesSinMovimiento,
                ],
                'semaforo' => [
                    'verde'    => $semaforoPorDias(0, 1),
                    'amarillo' => $semaforoPorDias(1, 30),
                    'naranja'  => $semaforoPorDias(30, 60),
                    'rojo'     => $semaforoPorDias(60, 90),
                    'negro'    => $semaforoPorDias(90),
                ],
                'genero'            => ['H' => $hombres, 'M' => $mujeres],
                'por_modalidad'     => $porModalidad,
                'por_municipio'     => $porMunicipio,
                'evolucion_mensual' => $evolucionMensual,
                'pagos_recientes'   => $pagosRecientes,
            ],
        ]);
    }
}
