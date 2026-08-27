<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ComprobacionUso;
use App\Models\Credito;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

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

        $hoy = now()->toDateString();
        $proxima = $credito->amortizaciones
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->sortBy('fecha_vencimiento')
            ->first();

        $diasMora = 0;
        $mora = 0.0;
        if ($proxima && $proxima->fecha_vencimiento < $hoy) {
            // Portal ciudadano: mismo fix de signo que PagoController. Mantener consistencia con AcreditadoController:238 y UpdateMoratorio:29.
            $diasMora = Carbon::parse($proxima->fecha_vencimiento)->diffInDays(now());
            if ($diasMora > 5) {
                $tasaDiaria = ($credito->tasaMoratoriaEfectiva() / 100) / 360;
                $mora = round((float) $proxima->saldo_insoluto * $tasaDiaria * $diasMora, 2);
            }
        }

        $proximaCuota = $proxima ? [
            'numero'            => $proxima->numero_cuota,
            'fecha_vencimiento' => $proxima->fecha_vencimiento,
            'capital'           => round((float) $proxima->capital_esperado - (float) $proxima->capital_pagado, 2),
            'interes'           => round((float) $proxima->interes_ordinario_esperado - (float) $proxima->interes_ordinario_pagado, 2),
            'mora'              => $mora,
            'total'             => round(
                ((float) $proxima->capital_esperado - (float) $proxima->capital_pagado)
                + ((float) $proxima->interes_ordinario_esperado - (float) $proxima->interes_ordinario_pagado)
                + $mora,
                2
            ),
            'dias_mora' => $diasMora,
            'vencida'   => $proxima->fecha_vencimiento < $hoy,
        ] : null;

        $comprobacion = ComprobacionUso::with('documentos')
            ->where('credito_id', $credito->id)
            ->latest()
            ->first();

        return Inertia::render('portal/MiCredito', [
            'comprobacion' => $comprobacion ? [
                'id'                       => $comprobacion->id,
                'estatus'                  => $comprobacion->estatus,
                'fecha_desembolso'         => $comprobacion->fecha_desembolso->format('d/m/Y'),
                'fecha_limite'             => $comprobacion->fecha_limite_comprobacion->format('d/m/Y'),
                'dias_restantes'           => $comprobacion->diasRestantes(),
                'semaforo'                 => $comprobacion->semaforo(),
                'documentos'               => $comprobacion->documentos->map(fn ($d) => [
                    'id'              => $d->id,
                    'tipo'            => $d->tipo,
                    'descripcion'     => $d->descripcion,
                    'monto'           => $d->monto,
                    'proveedor'       => $d->proveedor,
                    'nombre_original' => $d->nombre_original,
                    'url'             => $d->url,
                ]),
                'observaciones_operativo'  => $comprobacion->observaciones_operativo,
            ] : null,
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
                'proxima_cuota' => $proximaCuota,
            ],
            'datos_pago' => [
                'cie_bbva'      => '001776533',
                'cie_descripcion' => 'SERVICIOS',
                'clabe'         => '012914002017765339',
                'banco'         => 'BBVA Bancomer',
                'beneficiario'  => 'Instituto Yucateco de Emprendedores',
                'rfc'           => 'IIC991117V18',
                'concepto'      => 'Número de contrato: ' . ($credito->clave_contrato ?? '—'),
                'caja_horario'  => 'Lunes a Viernes, 9:00 a 14:00 hrs.',
                'correo'        => 'crea@iyemyucatan.com',
                'whatsapp'      => '9992342693',
            ],
        ]);
    }
}
