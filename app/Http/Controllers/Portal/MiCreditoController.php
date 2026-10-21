<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Amortizacion;
use App\Models\ComprobacionUso;
use App\Models\Credito;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

class MiCreditoController extends Controller
{
    public function index(): Response|RedirectResponse
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

        // P-07: "Total a pagar para ponerte al corriente" debe mostrar lo mismo que
        // le cobra el operativo en caja. Fuente de la verdad:
        // PagoController::resumenVencido() (PagoController.php:767-799) y la mora por
        // cuota de moraFilaPara() (PagoController.php:748-758), expuestas al operativo
        // en create() (PagoController.php:29-56) como total_a_pagar = pago_restante + mora.
        // Regla: costo de ponerse al día = suma, de TODAS las cuotas vencidas
        // (fecha_vencimiento <= hoy), de capital pendiente + interés ordinario pendiente
        // + mora. Misma zona horaria y misma base de mora que caja.
        $hoy = Carbon::now('America/Merida')->startOfDay();
        $tasaDiaria = ($credito->tasaMoratoriaEfectiva() / 100) / config('credito.dias_anio_comercial');

        // C-04: criterio único de etiquetas. Del día 1 al 5 de atraso la cuota está
        // "en gracia"; a partir del día 6 está "vencida". No cambia el cálculo de
        // mora (moraDeCuota), solo cómo se nombra la cuota en la pantalla.
        $diasGracia = (int) config('credito.dias_gracia_mora');

        $pendientes = $credito->amortizaciones
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->sortBy([['fecha_vencimiento', 'asc'], ['numero_cuota', 'asc']])
            ->values();

        // Mora real de una cuota (RO Cláusula 7a): días > config('credito.dias_gracia_mora')
        // sobre el saldo vencido, con la tasa moratoria efectiva diaria de caja.
        $moraDeCuota = function ($cuota) use ($hoy, $tasaDiaria): array {
            $vencimiento = Carbon::parse($cuota->fecha_vencimiento)->startOfDay();
            if ($hoy->lte($vencimiento)) {
                return ['dias' => 0, 'mora' => 0.0];
            }

            $dias = (int) $vencimiento->diffInDays($hoy);
            if ($dias <= config('credito.dias_gracia_mora')) {
                return ['dias' => $dias, 'mora' => 0.0];
            }

            $saldoVencido = round(max(0, (float) $cuota->saldo_insoluto - (float) $cuota->capital_pagado), 2);

            return ['dias' => $dias, 'mora' => round($saldoVencido * $tasaDiaria * $dias, 2)];
        };

        $cuotasVencidas = $pendientes
            ->filter(fn ($cuota) => $cuota->fecha_vencimiento->lte($hoy))
            ->map(function ($cuota) use ($moraDeCuota): array {
                $capital = round((float) $cuota->capital_esperado - (float) $cuota->capital_pagado, 2);
                $interes = round((float) $cuota->interes_ordinario_esperado - (float) $cuota->interes_ordinario_pagado, 2);
                ['dias' => $dias, 'mora' => $mora] = $moraDeCuota($cuota);

                return [
                    'numero'            => $cuota->numero_cuota,
                    'fecha_vencimiento' => $cuota->fecha_vencimiento?->format('d/m/Y'), //se pasa el formato para que lo respete MiCredito.vue
                    'capital'           => $capital,
                    'interes'           => $interes,
                    'mora'              => $mora,
                    'total'             => round($capital + $interes + $mora, 2),
                    'dias_mora'         => $dias,
                ];
            })
            ->values();

        if ($cuotasVencidas->isNotEmpty()) {
            // Varias cuotas vencidas: el total y la mora agregan todas, igual que caja.
            // La etiqueta depende del atraso máximo (C-04): 1..5 días = en gracia,
            // 6+ días = vencida. Vence hoy (0 días) sigue siendo la próxima cuota.
            $diasMoraMax = (int) $cuotasVencidas->max('dias_mora');
            $estadoCuota = match (true) {
                $diasMoraMax > $diasGracia => 'vencida',
                $diasMoraMax > 0           => 'gracia',
                default                    => 'proxima',
            };
            $proximaCuota = [
                'numero'            => $cuotasVencidas->first()['numero'],
                'cuotas'            => $cuotasVencidas->count(),
                'fecha_vencimiento' => $cuotasVencidas->first()['fecha_vencimiento'],
                'capital'           => round($cuotasVencidas->sum('capital'), 2),
                'interes'           => round($cuotasVencidas->sum('interes'), 2),
                'mora'              => round($cuotasVencidas->sum('mora'), 2),
                'total'             => round($cuotasVencidas->sum('total'), 2),
                'dias_mora'         => $diasMoraMax,
                'vencida'           => $estadoCuota === 'vencida',
                'estado'            => $estadoCuota,
            ];
        } else {
            // Al corriente: se conserva la vista de la próxima cuota (mora 0) igual que antes.
            $proxima = $pendientes->first();

            if ($proxima) {
                ['dias' => $diasMora, 'mora' => $mora] = $moraDeCuota($proxima);

                $proximaCuota = [
                    'numero'            => $proxima->numero_cuota,
                    'cuotas'            => 1,
                    'fecha_vencimiento' => $proxima->fecha_vencimiento?->format('d/m/Y'), //se pasa el formato para que lo respete MiCredito.vue
                    'capital'           => round((float) $proxima->capital_esperado - (float) $proxima->capital_pagado, 2),
                    'interes'           => round((float) $proxima->interes_ordinario_esperado - (float) $proxima->interes_ordinario_pagado, 2),
                    'mora'              => $mora,
                    'total'             => round(
                        ((float) $proxima->capital_esperado - (float) $proxima->capital_pagado)
                        + ((float) $proxima->interes_ordinario_esperado - (float) $proxima->interes_ordinario_pagado)
                        + $mora,
                        2
                    ),
                    'dias_mora'         => $diasMora,
                    'vencida'           => false,
                    'estado'            => 'proxima',
                ];
            } else {
                $proximaCuota = null;
            }
        }

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
            'dias_gracia' => $diasGracia,
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
                    'fecha_vencimiento' => $a->fecha_vencimiento?->format('d/m/Y'), //se cambia el formato para que lo respete MiCredito.vue
                    'saldo_insoluto' => $a->saldo_insoluto,
                    'capital'        => $a->capital_esperado,
                    'ordinario'      => $a->interes_ordinario_esperado,
                    'moratorio'      => $a->interes_moratorio_generado ?? 0,
                    'capital_pagado' => $a->capital_pagado,
                    'ordinario_pagado'=> $a->interes_ordinario_pagado,
                    'total_pagado'   => ($a->capital_pagado ?? 0) + ($a->interes_ordinario_pagado ?? 0),
                    'estado'         => $a->estado,
                    'estado_visual'  => $this->estadoVisualCuota($a, $hoy, $diasGracia),
                ]),
                'pagos' => $credito->pagos->map(fn($p) => [
                    'id'            => $p->id,
                    'folio'         => $p->folio,
                    'fecha_pago'    => $p->fecha_pago?->format('d/m/Y'),
                    'monto_recibido'=> $p->monto_recibido,
                    'forma_pago'    => $p->forma_pago,
                ]),
                'proxima_cuota'   => $proximaCuota,
                'cuotas_vencidas' => $cuotasVencidas,
            ],
            'datos_pago' => [
                'cie_bbva'      => '001776533',
                'cie_descripcion' => 'SERVICIOS',
                'clabe'         => '012914002017765339',
                'banco'         => 'BBVA Bancomer',
                'beneficiario'  => 'Instituto Yucateco de Emprendedores',
                'rfc'           => 'IIC991117V18',
                'concepto'      => 'Número de contrato: ' . ($credito->clave_contrato ?? '—'),
                'caja_horario'  => config('contacto.horario'),
                'correo'        => config('contacto.correo'),
                'whatsapp'      => '9992342693',
            ],
        ]);
    }

    /**
     * Etiqueta visual de una cuota para la tabla de amortización.
     *
     * C-04: mismo criterio que la tarjeta de próxima cuota y que Avisos.
     * 1..dias_gracia días de atraso = "en gracia"; más de dias_gracia = "vencida".
     * Solo etiqueta: no calcula mora, intereses ni saldos.
     */
    private function estadoVisualCuota(Amortizacion $cuota, Carbon $hoy, int $diasGracia): string
    {
        if ($cuota->estado === 'Gracia') {
            return 'gracia_periodo';
        }

        if ($cuota->estado === 'Condonado') {
            return 'condonado';
        }

        $total  = (float) $cuota->capital_esperado + (float) $cuota->interes_ordinario_esperado;
        $pagado = (float) $cuota->capital_pagado + (float) $cuota->interes_ordinario_pagado;

        if ($cuota->estado === 'Pagado' || $pagado >= $total) {
            return 'pagado';
        }

        // El cast 'date' deja la fecha a las 00:00 en la zona de la app (UTC);
        // se reconstruye en Mérida para no perder un día al comparar con $hoy.
        $vencimiento = Carbon::parse($cuota->fecha_vencimiento->toDateString(), 'America/Merida')->startOfDay();

        if ($vencimiento->gte($hoy)) {
            return 'pendiente';
        }

        $dias = (int) $vencimiento->diffInDays($hoy);

        return $dias > $diasGracia ? 'vencido' : 'en_gracia';
    }
}
