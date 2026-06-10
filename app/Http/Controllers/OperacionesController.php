<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\Pago;
use Inertia\Inertia;

class OperacionesController extends Controller
{
    public function index(Acreditado $acreditado)
    {
        $acreditado->load(['creditos.modalidad']);
        $credito = $acreditado->creditos->first();

        $operaciones = Pago::with(['cajero', 'canceladoPor'])
            ->where('acreditado_id', $acreditado->id)
            ->orderBy('fecha_pago', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($op) {
                $cuotas = collect($op->cuotas_cubiertas ?? []);

                return [
                    'id'                 => $op->id,
                    'folio'              => $op->folio,
                    'fecha_pago'         => $op->fecha_pago->format('Y-m-d'),
                    'forma_pago'         => $op->forma_pago,
                    'referencia'         => $op->referencia,
                    'monto_recibido'     => (float) $op->monto_recibido,
                    'aplicado_mora'      => (float) $op->aplicado_mora,
                    'aplicado_ordinario' => (float) $op->aplicado_ordinario,
                    'aplicado_capital'   => (float) $op->aplicado_capital,
                    'cuotas_cubiertas'   => $cuotas->map(fn($c) => [
                        'cuota'              => $c['cuota'],
                        'aplicado_capital'   => round((float)($c['cap'] ?? 0), 2),
                        'aplicado_ordinario' => round((float)($c['int'] ?? 0), 2),
                        'aplicado_mora'      => round((float)($c['mor'] ?? 0), 2),
                    ])->values()->all(),
                    'observaciones'      => $op->observaciones,
                    'registrado_por'     => $op->cajero?->name ?? 'Sistema',
                    'created_at'         => $op->created_at->format('Y-m-d H:i'),
                    'cancelado'          => (bool) $op->cancelado,
                    'cancelado_por'      => $op->canceladoPor?->name,
                    'cancelado_at'       => $op->cancelado_at?->format('Y-m-d H:i'),
                    'motivo_cancelacion' => $op->motivo_cancelacion,
                ];
            });

        // Solo activos para totales
        $activos = $operaciones->where('cancelado', false);

        $totales = [
            'monto_total'     => round($activos->sum('monto_recibido'), 2),
            'total_mora'      => round($activos->sum('aplicado_mora'), 2),
            'total_ordinario' => round($activos->sum('aplicado_ordinario'), 2),
            'total_capital'   => round($activos->sum('aplicado_capital'), 2),
            'num_pagos'       => $activos->count(),
        ];

        return Inertia::render('Operaciones/Index', [
            'acreditado'  => $acreditado,
            'credito'     => $credito,
            'operaciones' => $operaciones->values(),
            'totales'     => $totales,
        ]);
    }
}
