<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use Inertia\Inertia;
use Inertia\Response;

class MiCreditoController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $solicitud = $user->solicitudCredito;

        if (!$solicitud || !$solicitud->credito_id) {
            return redirect()->route('portal.dashboard');
        }

        $credito = Credito::with([
            'acreditado',
            'modalidad',
            'amortizaciones' => fn($q) => $q->orderBy('numero_cuota'),
            'pagos' => fn($q) => $q->where('cancelado', false)->orderByDesc('fecha_pago'),
        ])->findOrFail($solicitud->credito_id);

        return Inertia::render('portal/MiCredito', [
            'credito' => [
                'id'                    => $credito->id,
                'clave_contrato'        => $credito->clave_contrato,
                'monto_otorgado'        => $credito->monto_otorgado,
                'plazo_meses'           => $credito->plazo_meses,
                'fecha_entrega'         => $credito->fecha_entrega?->format('d/m/Y'),
                'tasa_interes_ordinario'=> $credito->tasa_interes_ordinario,
                'estatus'               => $credito->estatus,
                'modalidad'             => $credito->modalidad?->nombre,
                'acreditado'            => [
                    'nombre_completo' => $credito->acreditado->nombre_completo,
                    'municipio'       => $credito->acreditado->municipio,
                ],
                'tabla' => $credito->amortizaciones->map(fn($a) => [
                    'numero_cuota'   => $a->numero_cuota,
                    'fecha_vencimiento' => $a->fecha_vencimiento,
                    'saldo_insoluto' => $a->saldo_insoluto,
                    'capital'        => $a->capital_esperado,
                    'ordinario'      => $a->interes_ordinario_esperado,
                    'moratorio'      => $a->moratorio_acumulado ?? 0,
                    'capital_pagado' => $a->capital_pagado,
                    'ordinario_pagado'=> $a->interes_ordinario_pagado,
                    'total_pagado'   => ($a->capital_pagado ?? 0) + ($a->interes_ordinario_pagado ?? 0),
                    'estado'         => $a->estado,
                ]),
                'pagos' => $credito->pagos->map(fn($p) => [
                    'id'            => $p->id,
                    'folio'         => $p->folio,
                    'fecha_pago'    => $p->fecha_pago,
                    'monto_recibido'=> $p->monto_recibido,
                    'forma_pago'    => $p->forma_pago,
                ]),
            ],
        ]);
    }
}
