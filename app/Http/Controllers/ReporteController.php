<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\Pago;
use App\Models\Amortizacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function cartera(Request $request)
    {
        $estatus    = $request->get('estatus');
        $modId      = $request->get('modalidad_id') ? (int)$request->get('modalidad_id') : null;
        $sexo       = $request->get('sexo');
        $municipio  = $request->get('municipio');
        $hoy        = Carbon::now('America/Merida')->toDateString();

        $modalidades = \App\Models\ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        $creditosQuery = Credito::with(['acreditado', 'modalidad'])
            ->when($estatus, fn($q) => $q->where('estatus', $estatus))
            ->when($modId, fn($q) => $q->where('modalidad_id', $modId))
            ->when($sexo, fn($q) => $q->whereHas('acreditado', fn($a) => $a->where('sexo', $sexo)))
            ->when($municipio, fn($q) => $q->whereHas('acreditado', fn($a) => $a->where('municipio', $municipio)));

        $creditos = $creditosQuery->orderBy('created_at')->get()->map(function ($c) use ($hoy) {
            $capRec = (float) Pago::where('credito_id', $c->id)->where('cancelado', false)->sum('aplicado_capital');
            $ordRec = (float) Pago::where('credito_id', $c->id)->where('cancelado', false)->sum('aplicado_ordinario');
            $morRec = (float) Pago::where('credito_id', $c->id)->where('cancelado', false)->sum('aplicado_mora');

            $cuotasVencidas = Amortizacion::where('credito_id', $c->id)
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->where('fecha_vencimiento', '<', $hoy)
                ->count();

            // Cartera vencida: monto pendiente de cuotas ya vencidas
            $montoVencido = (float) Amortizacion::where('credito_id', $c->id)
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->where('fecha_vencimiento', '<', $hoy)
                ->sum('pago_restante');

            // Por vencer: monto pendiente de cuotas aún vigentes
            $montoPorVencer = (float) Amortizacion::where('credito_id', $c->id)
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->where('fecha_vencimiento', '>=', $hoy)
                ->sum('pago_restante');

            // Devengado: interés ordinario esperado en cuotas no pagadas aún no vencidas
            $devengado = (float) Amortizacion::where('credito_id', $c->id)
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->where('fecha_vencimiento', '>=', $hoy)
                ->sum(DB::raw('interes_ordinario_esperado - interes_ordinario_pagado'));

            return [
                'id'                 => $c->id,
                'clave_contrato'     => $c->clave_contrato,
                'acreditado'         => $c->acreditado?->nombre_completo,
                'municipio'          => $c->acreditado?->municipio,
                'modalidad'          => $c->modalidad?->nombre,
                'monto_otorgado'     => (float)$c->monto_otorgado,
                'plazo_meses'        => $c->plazo_meses,
                'tasa_ordinario'     => (float)$c->tasa_interes_ordinario,
                'tasa_moratoria'     => (float)$c->tasa_interes_moratorio,
                'fecha_entrega'      => $c->fecha_entrega ? Carbon::parse($c->fecha_entrega)->format('Y-m-d') : null,
                'estatus'            => $c->estatus,
                'capital_recuperado' => round($capRec, 2),
                'capital_pendiente'  => round((float)$c->monto_otorgado - $capRec, 2),
                'interes_cobrado'    => round($ordRec, 2),
                'mora_cobrada'       => round($morRec, 2),
                'cuotas_vencidas'    => $cuotasVencidas,
                'monto_vencido'      => round($montoVencido, 2),
                'monto_por_vencer'   => round($montoPorVencer, 2),
                'devengado'          => round(max(0, $devengado), 2),
            ];
        });

        $pagado = $creditos->sum('capital_recuperado') + $creditos->sum('interes_cobrado') + $creditos->sum('mora_cobrada');

        $resumen = [
            'total_creditos'    => $creditos->count(),
            'activos'           => $creditos->where('estatus', 'Activo')->count(),
            'morosos'           => $creditos->where('estatus', 'Moroso')->count(),
            'liquidados'        => $creditos->where('estatus', 'Liquidado')->count(),
            'monto_colocado'    => round($creditos->sum('monto_otorgado'), 2),
            'capital_recuperado'=> round($creditos->sum('capital_recuperado'), 2),
            'capital_pendiente' => round($creditos->sum('capital_pendiente'), 2),
            'interes_cobrado'   => round($creditos->sum('interes_cobrado'), 2),
            'mora_cobrada'      => round($creditos->sum('mora_cobrada'), 2),
            // Nuevas secciones
            'pagado'            => round($pagado, 2),
            'devengado'         => round($creditos->sum('devengado'), 2),
            'vencido'           => round($creditos->sum('monto_vencido'), 2),
            'por_vencer'        => round($creditos->sum('monto_por_vencer'), 2),
        ];

        // Municipios únicos de la cartera para el filtro
        $municipios = \App\Models\Acreditado::select('municipio')
            ->whereNotNull('municipio')->where('municipio', '!=', '')
            ->distinct()->orderBy('municipio')->pluck('municipio');

        return Inertia::render('Reportes/Cartera', [
            'creditos'    => $creditos->values(),
            'resumen'     => $resumen,
            'modalidades' => $modalidades,
            'municipios'  => $municipios,
            'filtros'     => ['estatus' => $estatus, 'modalidad_id' => $modId, 'sexo' => $sexo, 'municipio' => $municipio],
        ]);
    }

    public function pagos(Request $request)
    {
        $desde = $request->get('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->get('hasta', now()->toDateString());

        $pagos = Pago::with(['acreditado', 'cajero', 'credito'])
            ->where('cancelado', false)
            ->whereBetween('fecha_pago', [$desde, $hasta])
            ->orderBy('fecha_pago', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'                 => $p->id,
                'folio'              => $p->folio,
                'fecha_pago'         => $p->fecha_pago->format('Y-m-d'),
                'acreditado'         => $p->acreditado?->nombre_completo,
                'contrato'           => $p->credito?->clave_contrato,
                'forma_pago'         => $p->forma_pago,
                'monto_recibido'     => (float)$p->monto_recibido,
                'aplicado_mora'      => (float)$p->aplicado_mora,
                'aplicado_ordinario' => (float)$p->aplicado_ordinario,
                'aplicado_capital'   => (float)$p->aplicado_capital,
                'cajero'             => $p->cajero?->name ?? 'Sistema',
            ]);

        $totales = [
            'monto'     => round($pagos->sum('monto_recibido'), 2),
            'mora'      => round($pagos->sum('aplicado_mora'), 2),
            'ordinario' => round($pagos->sum('aplicado_ordinario'), 2),
            'capital'   => round($pagos->sum('aplicado_capital'), 2),
            'count'     => $pagos->count(),
        ];

        return Inertia::render('Reportes/Pagos', [
            'pagos'   => $pagos->values(),
            'totales' => $totales,
            'filtros' => compact('desde', 'hasta'),
        ]);
    }

    public function beneficiarios(Request $request)
    {
        $modalidadId = $request->get('modalidad_id') ? (int) $request->get('modalidad_id') : null;
        $municipio   = $request->get('municipio');
        $sexo        = $request->get('sexo');
        $estatus     = $request->get('estatus');

        $modalidades = \App\Models\ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        $creditos = Credito::with(['acreditado', 'modalidad'])
            ->whereNotNull('fecha_entrega')
            ->when($modalidadId, fn($q) => $q->where('modalidad_id', $modalidadId))
            ->when($estatus, fn($q) => $q->where('estatus', $estatus))
            ->when($municipio, fn($q) => $q->whereHas('acreditado', fn($a) => $a->where('municipio', $municipio)))
            ->when($sexo, fn($q) => $q->whereHas('acreditado', fn($a) => $a->where('sexo', $sexo)))
            ->orderBy('fecha_entrega', 'desc')
            ->get()
            ->map(fn($c) => [
                'id'                 => $c->id,
                'clave_contrato'     => $c->clave_contrato,
                'nombre'             => $c->acreditado?->nombre_completo,
                'curp'               => $c->acreditado?->curp,
                'sexo'               => $c->acreditado?->sexo,
                'municipio'          => $c->acreditado?->municipio,
                'modalidad'          => $c->modalidad?->nombre,
                'monto_otorgado'     => (float) $c->monto_otorgado,
                'plazo_meses'        => $c->plazo_meses,
                'fecha_entrega'      => $c->fecha_entrega?->format('Y-m-d'),
                'estatus'            => $c->estatus,
            ]);

        $porModalidad = $creditos->groupBy('modalidad')->map(fn($g) => [
            'cantidad'       => $g->count(),
            'monto_total'    => round($g->sum('monto_otorgado'), 2),
            'monto_promedio' => round($g->avg('monto_otorgado'), 2),
        ]);

        $municipios = Acreditado::select('municipio')
            ->whereNotNull('municipio')->where('municipio', '!=', '')
            ->distinct()->orderBy('municipio')->pluck('municipio');

        return Inertia::render('Reportes/Beneficiarios', [
            'beneficiarios'  => $creditos->values(),
            'por_modalidad'  => $porModalidad,
            'totales' => [
                'total'          => $creditos->count(),
                'mujeres'        => $creditos->where('sexo', 'Femenino')->count(),
                'hombres'        => $creditos->where('sexo', 'Masculino')->count(),
                'monto_total'    => round($creditos->sum('monto_otorgado'), 2),
                'monto_promedio' => round($creditos->avg('monto_otorgado'), 2),
            ],
            'modalidades' => $modalidades,
            'municipios'  => $municipios,
            'filtros'     => compact('modalidadId', 'municipio', 'sexo', 'estatus'),
        ]);
    }

    public function informeCobranza(Request $request)
    {
        $hoy         = Carbon::now('America/Merida')->toDateString();
        $modalidadId = $request->get('modalidad_id') ? (int) $request->get('modalidad_id') : null;
        $semaforo    = $request->get('semaforo');

        $modalidades = \App\Models\ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        // Créditos que tienen al menos una cuota vencida sin pagar
        $creditos = Credito::with(['acreditado', 'modalidad', 'amortizaciones'])
            ->whereHas('amortizaciones', fn($q) => $q
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->where('fecha_vencimiento', '<', $hoy)
            )
            ->when($modalidadId, fn($q) => $q->where('modalidad_id', $modalidadId))
            ->get()
            ->map(function ($c) use ($hoy) {
                $diasMora = $c->diasMora();
                $sf       = $c->semaforoRiesgo();

                $montoVencido = (float) $c->amortizaciones
                    ->filter(fn($a) => !in_array($a->estado, ['Pagado', 'Condonado']) && $a->fecha_vencimiento < $hoy)
                    ->sum('pago_restante');

                $cuotasVencidas = $c->amortizaciones
                    ->filter(fn($a) => !in_array($a->estado, ['Pagado', 'Condonado']) && $a->fecha_vencimiento < $hoy)
                    ->count();

                return [
                    'id'              => $c->id,
                    'clave_contrato'  => $c->clave_contrato,
                    'nombre'          => $c->acreditado?->nombre_completo,
                    'municipio'       => $c->acreditado?->municipio,
                    'telefono'        => $c->acreditado?->telefono,
                    'modalidad'       => $c->modalidad?->nombre,
                    'monto_otorgado'  => (float) $c->monto_otorgado,
                    'estatus'         => $c->estatus,
                    'dias_mora'       => $diasMora,
                    'cuotas_vencidas' => $cuotasVencidas,
                    'monto_vencido'   => round($montoVencido, 2),
                    'semaforo'        => $sf,
                ];
            })
            ->when($semaforo, fn($col) => $col->where('semaforo', $semaforo))
            ->sortByDesc('dias_mora');

        $carteraBruta   = (float) Credito::whereNotNull('fecha_entrega')->sum('monto_otorgado');
        $carteraVencida = round($creditos->sum('monto_vencido'), 2);
        $im             = $carteraBruta > 0 ? round(($carteraVencida / $carteraBruta) * 100, 2) : 0;

        $porSemaforo = $creditos->groupBy('semaforo')->map->count();

        return Inertia::render('Reportes/InformeCobranza', [
            'creditos'      => $creditos->values(),
            'kpis' => [
                'total_en_mora'   => $creditos->count(),
                'cartera_vencida' => $carteraVencida,
                'cartera_bruta'   => round($carteraBruta, 2),
                'indice_morosidad'=> $im,
                'por_semaforo'    => $porSemaforo,
            ],
            'modalidades' => $modalidades,
            'filtros'     => compact('modalidadId', 'semaforo'),
        ]);
    }

    public function adeudo(Request $request, Credito $credito)
    {
        $credito->load(['acreditado', 'modalidad', 'amortizaciones']);

        $fechaCalculo = $request->get('fecha_calculo')
            ? Carbon::parse($request->get('fecha_calculo'))->startOfDay()
            : Carbon::now('America/Merida')->startOfDay();

        $hoy        = $fechaCalculo;
        $tasaDiaria = ($credito->tasa_interes_moratorio / 100) / 360;

        $amortizaciones = [];
        $totalCapitalPendiente = 0;
        $totalMoraActual       = 0;
        $totalInteresPendiente = 0;

        foreach ($credito->amortizaciones as $fila) {
            $row = $fila->toArray();

            if ($fila->estado === 'Pagado' || $fila->estado === 'Condonado') {
                $row['mora_al_dia']   = 0;
                $row['dias_atraso']   = 0;
                $row['total_a_pagar'] = 0;
                $amortizaciones[] = $row;
                continue;
            }

            $vencimiento = Carbon::parse($fila->fecha_vencimiento)->startOfDay();
            $diasAtraso  = 0;
            $moraActual  = 0;

            if ($hoy->gt($vencimiento)) {
                $diasAtraso = (int) $vencimiento->diffInDays($hoy);
                if ($diasAtraso > 5) {
                    $sv         = max(0, (float)$fila->saldo_insoluto - (float)$fila->capital_pagado);
                    $moraActual = round($sv * $tasaDiaria * $diasAtraso, 2);
                }
            }

            $capPend  = max(0, round((float)$fila->capital_esperado - (float)$fila->capital_pagado, 2));
            $intPend  = max(0, round((float)$fila->interes_ordinario_esperado - (float)$fila->interes_ordinario_pagado, 2));
            $totalCuota = round($capPend + $intPend + $moraActual, 2);

            $row['mora_al_dia']   = $moraActual;
            $row['dias_atraso']   = $diasAtraso;
            $row['capital_pend']  = $capPend;
            $row['interes_pend']  = $intPend;
            $row['total_a_pagar'] = $totalCuota;
            $amortizaciones[] = $row;

            $totalCapitalPendiente += $capPend;
            $totalMoraActual       += $moraActual;
            $totalInteresPendiente += $intPend;
        }

        // Monto de liquidación: capital pendiente + mora al día (interés futuro se condona)
        $montoLiquidacion = round($totalCapitalPendiente + $totalMoraActual, 2);
        $totalAdeudo      = round($totalCapitalPendiente + $totalInteresPendiente + $totalMoraActual, 2);

        $pagosRealizados = Pago::where('credito_id', $credito->id)
            ->where('cancelado', false)
            ->orderBy('fecha_pago')
            ->get(['folio', 'fecha_pago', 'monto_recibido', 'aplicado_capital', 'aplicado_ordinario', 'aplicado_mora']);

        return Inertia::render('Reportes/Adeudo', [
            'credito'         => $credito,
            'acreditado'      => $credito->acreditado,
            'amortizaciones'  => $amortizaciones,
            'fecha_calculo'   => $hoy->toDateString(),
            'resumen' => [
                'capital_pendiente'  => round($totalCapitalPendiente, 2),
                'interes_pendiente'  => round($totalInteresPendiente, 2),
                'mora_acumulada'     => round($totalMoraActual, 2),
                'total_adeudo'       => $totalAdeudo,
                'monto_liquidacion'  => $montoLiquidacion,
                'nota_liquidacion'   => 'El monto de liquidación condona el interés ordinario de cuotas futuras conforme al Reglamento Operativo.',
            ],
            'pagos_realizados' => $pagosRealizados,
        ]);
    }
}
