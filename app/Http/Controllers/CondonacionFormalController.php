<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\CondonacionFormal;
use App\Models\Credito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CondonacionFormalController extends Controller
{
    public function create(Credito $credito): Response
    {
        $credito->load(['acreditado', 'modalidad', 'amortizaciones', 'condonaciones.autorizadoPor']);

        $saldoCapital   = $credito->amortizaciones->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])->sum('capital_esperado');
        $saldoIntereses = $credito->amortizaciones->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])->sum('interes_ordinario_esperado');
        $saldoMora      = $credito->amortizaciones->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])->sum('moratorio_acumulado');

        return Inertia::render('Creditos/CondonacionFormal', [
            'credito' => [
                'id'             => $credito->id,
                'clave_contrato' => $credito->clave_contrato,
                'estatus'        => $credito->estatus,
                'acreditado'     => $credito->acreditado?->nombre_completo,
                'acreditado_id'  => $credito->acreditado_id,
                'saldo_capital'  => round($saldoCapital, 2),
                'saldo_intereses'=> round($saldoIntereses, 2),
                'saldo_mora'     => round($saldoMora, 2),
            ],
            'condonaciones_previas' => $credito->condonaciones->map(fn($c) => [
                'fecha'           => $c->fecha_autorizacion->format('d/m/Y'),
                'tipo'            => $c->tipo,
                'monto_total'     => $c->montoTotal(),
                'numero_acuerdo'  => $c->numero_acuerdo,
                'autorizado_por'  => $c->autorizadoPor?->name,
            ]),
        ]);
    }

    public function store(Request $request, Credito $credito): RedirectResponse
    {
        $data = $request->validate([
            'tipo'                      => 'required|in:Parcial_Capital,Parcial_Intereses,Parcial_Mora,Total',
            'monto_condonado_capital'   => 'nullable|numeric|min:0',
            'monto_condonado_intereses' => 'nullable|numeric|min:0',
            'monto_condonado_mora'      => 'nullable|numeric|min:0',
            'motivo'                    => 'required|string|max:1000',
            'numero_acuerdo'            => 'nullable|string|max:100',
            'fecha_autorizacion'        => 'required|date',
            'observaciones'             => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($credito, $data) {
            $condonacion = CondonacionFormal::create([
                'credito_id'                => $credito->id,
                'tipo'                      => $data['tipo'],
                'monto_condonado_capital'   => $data['monto_condonado_capital'] ?? 0,
                'monto_condonado_intereses' => $data['monto_condonado_intereses'] ?? 0,
                'monto_condonado_mora'      => $data['monto_condonado_mora'] ?? 0,
                'motivo'                    => $data['motivo'],
                'numero_acuerdo'            => $data['numero_acuerdo'] ?? null,
                'autorizado_por'            => auth()->id(),
                'fecha_autorizacion'        => $data['fecha_autorizacion'],
                'observaciones'             => $data['observaciones'] ?? null,
            ]);

            // Si es condonación total, liquidar todas las cuotas pendientes
            if ($data['tipo'] === 'Total') {
                $credito->amortizaciones()
                    ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])
                    ->update(['estado' => 'Condonado', 'pago_restante' => 0, 'moratorio_acumulado' => 0]);
                $credito->update(['estatus' => 'Liquidado']);
            }

            $montoTotal = ($data['monto_condonado_capital'] ?? 0)
                        + ($data['monto_condonado_intereses'] ?? 0)
                        + ($data['monto_condonado_mora'] ?? 0);

            AuditoriaLog::registrar(
                'Credito',
                $credito->id,
                'updated',
                'condonacion_formal',
                null,
                $data['tipo'],
                "Condonación formal registrada. Tipo: {$data['tipo']}. " .
                "Monto total: \${$montoTotal}. Acuerdo: " . ($data['numero_acuerdo'] ?? 'N/A')
            );

            return redirect()->route('acreditados.show', $credito->acreditado_id)
                ->with('success', 'Condonación formal registrada correctamente.');
        });
    }
}
