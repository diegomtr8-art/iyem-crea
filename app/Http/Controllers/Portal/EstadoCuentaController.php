<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EstadoCuentaController extends Controller
{
    private function getCreditoUsuario(): ?Credito
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;
        if (!$solicitud || !$solicitud->credito_id) return null;

        return Credito::with(['acreditado', 'modalidad', 'amortizaciones'])
            ->find($solicitud->credito_id);
    }

    public function pdf(): \Symfony\Component\HttpFoundation\Response
    {
        $credito = $this->getCreditoUsuario();
        abort_if(!$credito, 404, 'No tienes un crédito activo.');

        $pagos = Pago::where('credito_id', $credito->id)
            ->where('cancelado', false)
            ->orderBy('fecha_pago')
            ->get();

        $saldoPendiente = $credito->amortizaciones->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])->sum('pago_restante');

        $resumen = [
            'capital_pagado'  => round($pagos->sum('aplicado_capital'), 2),
            'interes_pagado'  => round($pagos->sum('aplicado_ordinario'), 2),
            'mora_pagada'     => round($pagos->sum('aplicado_mora'), 2),
            'saldo_pendiente' => round($saldoPendiente, 2),
            'cuotas_pagadas'  => $credito->amortizaciones->whereIn('estado', ['Pagado', 'Condonado'])->count(),
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.estado-cuenta', [
            'credito'        => $credito,
            'amortizaciones' => $credito->amortizaciones->sortBy('numero_cuota'),
            'pagos'          => $pagos,
            'resumen'        => $resumen,
        ]);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("EstadoCuenta_{$credito->clave_contrato}.pdf");
    }

    public function liquidacionAnticipada(): JsonResponse
    {
        $credito = $this->getCreditoUsuario();
        abort_if(!$credito, 404);

        $hoy = now()->toDateString();
        $amortizacionesPendientes = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->orderBy('numero_cuota')
            ->get();

        $totalCapital   = $amortizacionesPendientes->sum('capital_esperado') - $amortizacionesPendientes->sum('capital_pagado');
        $totalIntereses = 0;
        $totalMora      = 0;

        foreach ($amortizacionesPendientes as $cuota) {
            if ($cuota->fecha_vencimiento >= $hoy) {
                $diasRestantes = now()->diffInDays($cuota->fecha_vencimiento, false);
                if ($diasRestantes > 0) {
                    $tasaDiaria = ((float)$credito->tasa_interes_ordinario / 100) / 360;
                    $interesDias = ((float)$cuota->saldo_insoluto - (float)$cuota->capital_pagado) * $tasaDiaria * $diasRestantes;
                    $totalIntereses += max(0, round($interesDias, 2));
                }
            } else {
                $totalIntereses += max(0, (float)$cuota->interes_ordinario_esperado - (float)$cuota->interes_ordinario_pagado);
                $totalMora += (float)($cuota->moratorio_acumulado ?? 0);
            }
        }

        return response()->json([
            'capital_pendiente'  => round(max(0, $totalCapital), 2),
            'interes_proyectado' => round($totalIntereses, 2),
            'mora_acumulada'     => round($totalMora, 2),
            'total_liquidacion'  => round(max(0, $totalCapital) + $totalIntereses + $totalMora, 2),
            'fecha_calculo'      => now()->format('d/m/Y'),
        ]);
    }
}
