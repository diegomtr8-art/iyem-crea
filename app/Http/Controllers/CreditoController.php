<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\Credito;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class CreditoController extends Controller
{
    /**
     * Condonación total del saldo por fallecimiento u otro motivo autorizado.
     * Marca todas las cuotas pendientes como Condonado y el crédito como Liquidado.
     */
    public function condonar(Request $request, Credito $credito)
    {
        $validated = $request->validate([
            'motivo'     => 'required|string|max:500',
            'tipo_causa' => 'required|in:Fallecimiento,Autorización Directiva,Caso Social,Otro',
        ]);

        abort_if($credito->estatus === 'Liquidado', 422, 'El crédito ya está liquidado.');
        abort_if($credito->estatus === 'Cancelado', 422, 'El crédito está cancelado.');

        DB::transaction(function () use ($credito, $validated) {
            $capitalCondonado   = 0;
            $interesCondonado   = 0;
            $moratorioCondonado = 0;

            $cuotasPendientes = $credito->amortizaciones()
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])
                ->lockForUpdate()
                ->get();

            foreach ($cuotasPendientes as $cuota) {
                $capitalCondonado   += round((float)$cuota->capital_esperado - (float)$cuota->capital_pagado, 2);
                $interesCondonado   += round((float)$cuota->interes_ordinario_esperado - (float)$cuota->interes_ordinario_pagado, 2);
                $moratorioCondonado += (float)$cuota->interes_moratorio_generado;

                $cuota->update([
                    'capital_pagado'             => $cuota->capital_esperado,
                    'interes_ordinario_pagado'   => $cuota->interes_ordinario_esperado,
                    'interes_moratorio_generado' => 0,
                    'pago_restante'              => 0,
                    'estado'                     => 'Condonado',
                    'fecha_ultimo_pago'          => now(),
                ]);
            }

            // Registrar pago ficticio de $0 para auditoría
            Pago::create([
                'credito_id'         => $credito->id,
                'acreditado_id'      => $credito->acreditado_id,
                'folio'              => Pago::generarFolio(),
                'monto_recibido'     => 0,
                'aplicado_mora'      => 0,
                'aplicado_ordinario' => 0,
                'aplicado_capital'   => 0,
                'forma_pago'         => 'Condonación',
                'fecha_pago'         => now()->toDateString(),
                'observaciones'      => "CONDONACIÓN [{$validated['tipo_causa']}]: {$validated['motivo']}\n"
                    . "Capital condonado: $" . number_format($capitalCondonado, 2) . "\n"
                    . "Interés condonado: $" . number_format($interesCondonado, 2) . "\n"
                    . "Mora condonada: $" . number_format($moratorioCondonado, 2),
                'cuotas_cubiertas'   => [],
                'registrado_por'     => Auth::id(),
            ]);

            $credito->update(['estatus' => 'Liquidado']);
        });

        return redirect()->route('acreditados.show', $credito->acreditado_id)
            ->with('success', 'Condonación aplicada. El crédito ha sido cerrado.');
    }

    /**
     * Vista del Dictamen de Conclusión (cuando el crédito está Liquidado).
     */
    public function dictamen(Credito $credito)
    {
        abort_if($credito->estatus !== 'Liquidado', 403, 'El crédito no está liquidado.');

        $credito->load(['acreditado', 'modalidad', 'amortizaciones']);

        $pagosActivos = Pago::where('credito_id', $credito->id)
            ->where('cancelado', false)
            ->orderBy('fecha_pago')
            ->get();

        $totalCapital   = round($pagosActivos->sum('aplicado_capital'), 2);
        $totalOrdinario = round($pagosActivos->sum('aplicado_ordinario'), 2);
        $totalMora      = round($pagosActivos->sum('aplicado_mora'), 2);
        $totalPagado    = round($pagosActivos->sum('monto_recibido'), 2);

        $primerPago = $pagosActivos->first();
        $ultimoPago = $pagosActivos->last();

        $cuotasCondonadas = $credito->amortizaciones->where('estado', 'Condonado')->count();

        return Inertia::render('Creditos/Dictamen', [
            'credito' => [
                'id'              => $credito->id,
                'clave_contrato'  => $credito->clave_contrato,
                'monto_otorgado'  => (float)$credito->monto_otorgado,
                'plazo_meses'     => $credito->plazo_meses,
                'tasa_ordinario'  => (float)$credito->tasa_interes_ordinario,
                'tasa_moratoria'  => (float)$credito->tasa_interes_moratorio,
                'fecha_entrega'   => $credito->fecha_entrega ? Carbon::parse($credito->fecha_entrega)->format('Y-m-d') : null,
                'modalidad'       => $credito->modalidad?->nombre,
                'estatus'         => $credito->estatus,
            ],
            'acreditado' => [
                'nombre_completo' => $credito->acreditado?->nombre_completo,
                'municipio'       => $credito->acreditado?->municipio,
                'rfc'             => $credito->acreditado?->rfc,
                'curp'            => $credito->acreditado?->curp,
            ],
            'resumen' => [
                'total_pagado'      => $totalPagado,
                'total_capital'     => $totalCapital,
                'total_ordinario'   => $totalOrdinario,
                'total_mora'        => $totalMora,
                'num_pagos'         => $pagosActivos->count(),
                'primer_pago'       => $primerPago?->fecha_pago->format('Y-m-d'),
                'ultimo_pago'       => $ultimoPago?->fecha_pago->format('Y-m-d'),
                'cuotas_condonadas' => $cuotasCondonadas,
                'fecha_liquidacion' => $ultimoPago?->fecha_pago->format('Y-m-d'),
            ],
            'pagos' => $pagosActivos->map(fn($p) => [
                'folio'              => $p->folio,
                'fecha_pago'         => $p->fecha_pago->format('Y-m-d'),
                'monto_recibido'     => (float)$p->monto_recibido,
                'aplicado_capital'   => (float)$p->aplicado_capital,
                'aplicado_ordinario' => (float)$p->aplicado_ordinario,
                'aplicado_mora'      => (float)$p->aplicado_mora,
                'forma_pago'         => $p->forma_pago,
            ])->values(),
        ]);
    }

    public function contratoPdf(Credito $credito)
    {
        $credito->load(['acreditado', 'modalidad', 'amortizaciones']);

        $monto = (float) $credito->monto_otorgado;
        $montoLetras = $this->numeroALetras($monto);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.contrato-credito', [
            'credito'        => $credito,
            'amortizaciones' => $credito->amortizaciones->sortBy('numero_cuota'),
            'montoLetras'    => $montoLetras,
        ]);
        $pdf->setPaper('letter', 'portrait');

        AuditoriaLog::registrar('Credito', $credito->id, 'updated', 'contrato_descargado', null, now()->toDateTimeString(), 'Contrato PDF descargado');

        return $pdf->download("Contrato_{$credito->clave_contrato}.pdf");
    }

    public function estadoCuentaPdf(Credito $credito)
    {
        $credito->load(['acreditado', 'modalidad', 'amortizaciones']);

        $pagos = Pago::where('credito_id', $credito->id)
            ->where('cancelado', false)
            ->orderBy('fecha_pago')
            ->get();

        $cuotasPagadas = $credito->amortizaciones->whereIn('estado', ['Pagado', 'Condonado'])->count();
        $saldoPendiente = $credito->amortizaciones->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])->sum('pago_restante');

        $resumen = [
            'capital_pagado'  => round($pagos->sum('aplicado_capital'), 2),
            'interes_pagado'  => round($pagos->sum('aplicado_ordinario'), 2),
            'mora_pagada'     => round($pagos->sum('aplicado_mora'), 2),
            'saldo_pendiente' => round($saldoPendiente, 2),
            'cuotas_pagadas'  => $cuotasPagadas,
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.estado-cuenta', [
            'credito'       => $credito,
            'amortizaciones'=> $credito->amortizaciones->sortBy('numero_cuota'),
            'pagos'         => $pagos,
            'resumen'       => $resumen,
        ]);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("EstadoCuenta_{$credito->clave_contrato}.pdf");
    }

    public function constanciaPdf(Credito $credito)
    {
        abort_if($credito->estatus !== 'Liquidado', 403, 'Solo se puede generar constancia para créditos liquidados.');

        $credito->load(['acreditado', 'modalidad']);

        $pagos = Pago::where('credito_id', $credito->id)->where('cancelado', false)->get();
        $totalPagado = round($pagos->sum('monto_recibido'), 2);
        $fechaLiquidacion = $pagos->max('fecha_pago');

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.constancia-no-adeudo', [
            'credito'          => $credito,
            'totalPagado'      => $totalPagado,
            'fechaLiquidacion' => $fechaLiquidacion ? Carbon::parse($fechaLiquidacion) : null,
        ]);
        $pdf->setPaper('letter', 'portrait');

        AuditoriaLog::registrar('Credito', $credito->id, 'updated', 'constancia_descargada', null, now()->toDateTimeString(), 'Constancia de no adeudo generada');

        return $pdf->download("ConstanciaNoAdeudo_{$credito->clave_contrato}.pdf");
    }

    private function numeroALetras(float $monto): string
    {
        $entero = (int) floor($monto);
        $decimales = (int) round(($monto - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
            'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE',
            'DIECIOCHO', 'DIECINUEVE', 'VEINTE'];
        $decenas = ['', '', 'VEINTI', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
            'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero === 0) return 'CERO PESOS ' . sprintf('%02d', $decimales) . '/100 M.N.';
        if ($entero === 100) return 'CIEN PESOS ' . sprintf('%02d', $decimales) . '/100 M.N.';
        if ($entero === 1000) return 'MIL PESOS ' . sprintf('%02d', $decimales) . '/100 M.N.';

        $texto = '';
        if ($entero >= 1000) {
            $miles = (int) ($entero / 1000);
            $entero = $entero % 1000;
            if ($miles === 1) $texto .= 'MIL ';
            elseif ($miles <= 20) $texto .= $unidades[$miles] . ' MIL ';
            else $texto .= $decenas[(int)($miles/10)] . ($miles%10 ? ' Y ' . $unidades[$miles%10] : '') . ' MIL ';
        }
        if ($entero >= 100) {
            $texto .= $centenas[(int)($entero/100)] . ' ';
            $entero = $entero % 100;
        }
        if ($entero > 0) {
            if ($entero <= 20) $texto .= $unidades[$entero];
            else $texto .= $decenas[(int)($entero/10)] . ($entero % 10 ? ($entero < 30 ? $unidades[$entero%10] : ' Y ' . $unidades[$entero%10]) : '');
        }

        return trim($texto) . ' PESOS ' . sprintf('%02d', $decimales) . '/100 M.N.';
    }

    /**
     * Genera el PDF del Dictamen de Conclusión.
     */
    public function dictamenPdf(Credito $credito)
    {
        abort_if($credito->estatus !== 'Liquidado', 403, 'El crédito no está liquidado.');

        $credito->load(['acreditado', 'modalidad', 'amortizaciones']);

        $pagosActivos = Pago::where('credito_id', $credito->id)
            ->where('cancelado', false)
            ->orderBy('fecha_pago')
            ->get();

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.dictamen-conclusion', [
            'credito'     => $credito,
            'pagos'       => $pagosActivos,
            'totalCapital'   => round($pagosActivos->sum('aplicado_capital'), 2),
            'totalOrdinario' => round($pagosActivos->sum('aplicado_ordinario'), 2),
            'totalMora'      => round($pagosActivos->sum('aplicado_mora'), 2),
            'generadoEn'     => now()->format('d/m/Y H:i'),
        ]);
        $pdf->setPaper('letter', 'portrait');

        return $pdf->download("Dictamen_{$credito->clave_contrato}.pdf");
    }
}
