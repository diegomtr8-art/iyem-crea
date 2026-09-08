<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\Pago;
use App\Models\Amortizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Carbon\Carbon;

class PagoController extends Controller
{
    public function create(Credito $credito)
    {
        $credito->load(['acreditado:id,nombre_completo', 'modalidad']);

        abort_if($credito->estatus === 'Liquidado', 403, 'Este crédito ya está liquidado.');
        abort_if($credito->estatus === 'Cancelado', 403, 'Este crédito está cancelado.');

        $hoy = Carbon::now('America/Merida')->startOfDay();
        $tasaDiaria = ($credito->tasaMoratoriaEfectiva() / 100) / 360;

        $cuotasPendientes = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->orderBy('numero_cuota', 'asc')
            ->get()
            ->map(function ($cuota) use ($hoy, $tasaDiaria) {
                $vencimiento = Carbon::parse($cuota->fecha_vencimiento)->startOfDay();
                $mora = 0.0;

                if ($hoy->gt($vencimiento)) {
                    $dias = (int) $vencimiento->diffInDays($hoy);
                    if ($dias > 5) {
                        $saldoVencido = round(max(0, (float)$cuota->saldo_insoluto - (float)$cuota->capital_pagado), 2);
                        $mora = round($saldoVencido * $tasaDiaria * $dias, 2);
                    }
                }

                return [
                    'id'                => $cuota->id,
                    'numero_cuota'      => $cuota->numero_cuota,
                    'fecha_vencimiento' => $cuota->fecha_vencimiento,
                    'capital_pendiente' => round((float)$cuota->capital_esperado - (float)$cuota->capital_pagado, 2),
                    'interes_pendiente' => round((float)$cuota->interes_ordinario_esperado - (float)$cuota->interes_ordinario_pagado, 2),
                    'saldo_vencido'     => round(max(0, (float)$cuota->saldo_insoluto - (float)$cuota->capital_pagado), 2),
                    'mora_al_dia'       => $mora,
                    'pago_restante'     => (float) $cuota->pago_restante,
                    'total_a_pagar'     => round((float)$cuota->pago_restante + $mora, 2),
                ];
            });

        return Inertia::render('Pagos/Index', [
            'credito'           => $credito,
            'acreditado'        => $credito->acreditado,
            'cuotas_pendientes' => $cuotasPendientes,
            'formas_pago'       => ['Efectivo', 'Transferencia', 'Cheque', 'Tarjeta'],
            'tasa_mora'         => (float) $credito->tasa_interes_moratorio,
        ]);
    }

    public function store(Request $request, Credito $credito)
    {
        $validated = $request->validate([
            'monto_recibido' => 'required|numeric|min:0.01',
            'fecha_pago'     => 'required|date|before_or_equal:today',
            'forma_pago'     => 'required|in:Efectivo,Transferencia,Cheque,Tarjeta',
            'tipo_abono'     => 'nullable|in:Adelantado,Reducir Cuota,Reducir Plazo',
            'referencia'     => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string|max:1000',
        ]);

        abort_if($credito->estatus === 'Liquidado', 403, 'Este crédito ya está liquidado.');
        abort_if($credito->estatus === 'Cancelado', 403, 'Este crédito está cancelado.');

        $pagoId = DB::transaction(function () use ($validated, $credito) {
            $hoy           = Carbon::parse($validated['fecha_pago'])->startOfDay();
            $montoRestante = (float) $validated['monto_recibido'];
            $tasaDiaria    = ($credito->tasaMoratoriaEfectiva() / 100) / 360;

            $capitalPendienteTotal = round((float) $credito->amortizaciones()
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->lockForUpdate()
                ->sum(DB::raw('capital_esperado - capital_pagado')), 2);

            $esLiquidacionTotal = ($montoRestante >= ($capitalPendienteTotal - 0.01));
            $interesCondonado   = 0;
            $detallesAuditoria  = [];

            $totalAplicadoMora      = 0;
            $totalAplicadoOrdinario = 0;
            $totalAplicadoCapital   = 0;

            $cuotas = $credito->amortizaciones()
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->lockForUpdate()
                ->orderBy('numero_cuota', 'asc')
                ->get();

            // Resumen de lo vencido para las validaciones previas (Ticket 4.3)
            $resumen = $this->resumenVencido($cuotas, $hoy, $tasaDiaria);
            $maxLegal = round(
                $resumen['capitalPendiente'] + $resumen['interesVencido'] + $resumen['moraTotal'],
                2
            );

            // Gate 1 — Exceso. Dos topes según el tipo de pago:
            //  - Liquidación: capital pendiente + mora (el interés futuro se condona).
            //  - Pago normal: capital pendiente + intereses vencidos + mora.
            if ($esLiquidacionTotal) {
                $topeLiquidacion = round($resumen['capitalPendiente'] + $resumen['moraTotal'], 2);
                if ($montoRestante > $topeLiquidacion + 0.01) {
                    throw ValidationException::withMessages([
                        'monto_recibido' => 'El importe excede el monto de liquidación del crédito ($'
                            . number_format($topeLiquidacion, 2)
                            . '). Verifique la cantidad: no se puede cobrar más de lo que adeuda el ciudadano.',
                    ]);
                }
            } elseif ($montoRestante > $maxLegal + 0.01) {
                throw ValidationException::withMessages([
                    'monto_recibido' => 'El importe excede lo máximo que se puede recibir en este pago ($'
                        . number_format($maxLegal, 2)
                        . '). Verifique la cantidad.',
                ]);
            }

            // Gate 2 — Mora: primero ponerse al día (cubrir cuotas vencidas).
            // Mientras exista mora no se permite adelantar, abonar a capital ni liquidar.
            if ($resumen['moraTotal'] > 0.01) {
                $costoEstarAlDia = round($resumen['costoEstarAlDia'], 2);
                if ($montoRestante > $costoEstarAlDia + 0.01) {
                    throw ValidationException::withMessages([
                        'monto_recibido' => 'El crédito tiene mora. Primero debe cubrir las cuotas vencidas ($'
                            . number_format($costoEstarAlDia, 2)
                            . ') para ponerse al día; después podrá abonar a capital o liquidar.',
                    ]);
                }
            }

            // Ruteo del sobrante (Ticket 4.3): detecta si el pago, tras cubrir la cuota
            // corriente, deja un sobrante de 1 cuota completa o más (requiere elección).
            $tipoAbono         = $validated['tipo_abono'] ?? null;
            $hayEscenario      = false;
            $detenerEnCorriente = false;
            $primera           = $cuotas->first();

            if (!$esLiquidacionTotal && $resumen['moraTotal'] <= 0.01 && $primera) {
                $costoPrimera          = round((float) $primera->pago_restante, 2);
                $sobranteTrasCorriente = round($montoRestante - $costoPrimera, 2);
                $siguiente             = $cuotas->get(1);

                if ($siguiente && $sobranteTrasCorriente >= (round((float) $siguiente->pago_restante, 2) - 0.01)) {
                    $hayEscenario = true;
                    $opciones     = ['Adelantado', 'Reducir Cuota', 'Reducir Plazo'];

                    if (!in_array($tipoAbono, $opciones, true)) {
                        throw ValidationException::withMessages([
                            'tipo_abono' => 'El pago deja un sobrante de una cuota o más. '
                                . 'Seleccione cómo aplicarlo: Adelantado, Reducir cuota o Reducir plazo.',
                        ]);
                    }

                    $detenerEnCorriente = in_array($tipoAbono, ['Reducir Cuota', 'Reducir Plazo'], true);
                }
            }

            // Snapshot del estado previo de las cuotas pendientes (único conjunto
            // que este pago puede modificar) para permitir una reversión exacta al cancelar.
            $snapshotAmortizaciones = $cuotas->map(fn($c) => $c->only([
                'id',
                'numero_cuota',
                'capital_esperado',
                'interes_ordinario_esperado',
                'cuota_fija',
                'saldo_insoluto',
                'capital_pagado',
                'interes_ordinario_pagado',
                'interes_moratorio_pagado',
                'interes_moratorio_generado',
                'moratorio_acumulado',
                'pago_restante',
                'estado',
                'fecha_ultimo_pago',
            ]))->values();

            foreach ($cuotas as $fila) {
                if ($montoRestante < 0.01) break;

                $vencimiento = Carbon::parse($fila->fecha_vencimiento)->startOfDay();

                // Condonar intereses futuros en liquidación total
                if ($esLiquidacionTotal && $vencimiento->gt($hoy)) {
                    $pendienteInteres = round((float)$fila->interes_ordinario_esperado - (float)$fila->interes_ordinario_pagado, 2);
                    $interesCondonado += $pendienteInteres;
                    $fila->interes_ordinario_esperado = $fila->interes_ordinario_pagado;
                }

                // Mora sobre saldo insoluto vencido (RO Cláusula 7a)
                $moraFila = 0;
                if ($hoy->gt($vencimiento)) {
                    // Carbon 3: diffInDays() es con signo. Usar $vencimiento->diffInDays($hoy) para obtener días positivos cuando está vencida.
                    $dias = (int) $vencimiento->diffInDays($hoy);
                    if ($dias > 5) {
                        $saldoVencido = round(max(0, (float)$fila->saldo_insoluto - (float)$fila->capital_pagado), 2);
                        $moraFila = round($saldoVencido * $tasaDiaria * $dias, 2);
                    }
                }

                // Cascada: Mora → Interés Ordinario → Capital
                $pagoMora = min($montoRestante, $moraFila);
                $montoRestante -= $pagoMora;

                $ordPendiente = round((float)$fila->interes_ordinario_esperado - (float)$fila->interes_ordinario_pagado, 2);
                $pagoOrd = min($montoRestante, max(0, $ordPendiente));
                $montoRestante -= $pagoOrd;

                $capPendiente = round((float)$fila->capital_esperado - (float)$fila->capital_pagado, 2);
                $pagoCap = min($montoRestante, max(0, $capPendiente));
                $montoRestante -= $pagoCap;

                $nuevoCapPagado = round((float)$fila->capital_pagado + $pagoCap, 2);
                $nuevoOrdPagado = round((float)$fila->interes_ordinario_pagado + $pagoOrd, 2);

                $estaLiquidada = (
                    $nuevoCapPagado >= ((float)$fila->capital_esperado - 0.01) &&
                    $nuevoOrdPagado >= ((float)$fila->interes_ordinario_esperado - 0.01)
                );

                $nuevoSaldoInsoluto = round(max(0, (float)$fila->saldo_insoluto - $pagoCap), 2);

                $fila->update([
                    'capital_pagado'             => $nuevoCapPagado,
                    'interes_ordinario_pagado'   => $nuevoOrdPagado,
                    'interes_moratorio_pagado'   => round((float)$fila->interes_moratorio_pagado + $pagoMora, 2),
                    'interes_moratorio_generado' => $moraFila,
                    'saldo_insoluto'             => $nuevoSaldoInsoluto,
                    'pago_restante'              => max(0, round(
                        ((float)$fila->capital_esperado + (float)$fila->interes_ordinario_esperado)
                        - ($nuevoCapPagado + $nuevoOrdPagado),
                        2
                    )),
                    'estado'            => $estaLiquidada ? 'Pagado' : (($nuevoCapPagado > 0 || $nuevoOrdPagado > 0) ? 'Parcial' : 'Pendiente'),
                    'fecha_ultimo_pago' => $hoy,
                ]);

                $totalAplicadoMora      += $pagoMora;
                $totalAplicadoOrdinario += $pagoOrd;
                $totalAplicadoCapital   += $pagoCap;

                if ($pagoMora > 0 || $pagoOrd > 0 || $pagoCap > 0) {
                    $detallesAuditoria[] = [
                        'cuota' => $fila->numero_cuota,
                        'cap'   => $pagoCap,
                        'int'   => $pagoOrd,
                        'mor'   => $pagoMora,
                    ];
                }

                // Reducir cuota/plazo: no se pre-pagan cuotas siguientes; el sobrante
                // queda disponible para abonarse a capital tras la cuota corriente.
                if ($detenerEnCorriente && $fila->numero_cuota === $primera->numero_cuota) {
                    break;
                }
            }

            // Aplicar sobrante a capital (pago anticipado) cuando el operativo eligió
            // Reducir cuota / Reducir plazo. (Provisional: el Commit 3/4 reemplaza la
            // matemática interna de aplicarAbonoCapital.)
            if ($montoRestante > 0.01 && $detenerEnCorriente) {
                $this->aplicarAbonoCapital($credito, $montoRestante, $tipoAbono, $hoy);
                $totalAplicadoCapital += $montoRestante;
            }

            $notaCondonacion = $interesCondonado > 0
                ? "\n*** CONDONACIÓN POR ANTICIPO: $" . number_format($interesCondonado, 2)
                : '';

            $pago = Pago::create([
                'credito_id'         => $credito->id,
                'acreditado_id'      => $credito->acreditado_id,
                'folio'              => Pago::generarFolio(),
                'monto_recibido'     => $validated['monto_recibido'],
                'aplicado_mora'      => round($totalAplicadoMora, 2),
                'aplicado_ordinario' => round($totalAplicadoOrdinario, 2),
                'aplicado_capital'   => round($totalAplicadoCapital, 2),
                'forma_pago'         => $validated['forma_pago'],
                'referencia'         => $validated['referencia'] ?? null,
                'fecha_pago'         => $validated['fecha_pago'],
                'observaciones'      => ($validated['observaciones'] ?? 'Pago registrado') . $notaCondonacion,
                'cuotas_cubiertas'   => $detallesAuditoria,
                'snapshot_amortizaciones' => $snapshotAmortizaciones,
                'registrado_por'     => Auth::id(),
            ]);

            // Actualizar estatus del crédito (excluir estados finales)
            $activasQuery = fn() => $credito->amortizaciones()
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia']);

            if ($activasQuery()->count() === 0) {
                $credito->update(['estatus' => 'Liquidado']);
            } elseif ($activasQuery()->where('fecha_vencimiento', '<', now())->exists()) {
                $credito->update(['estatus' => 'Moroso']);
            } else {
                $credito->update(['estatus' => 'Activo']);
            }

            return $pago->id;
        });

        return redirect()->route('pagos.recibo', $pagoId)
            ->with('success', 'Pago registrado exitosamente.');
    }

    /**
     * Aplica sobrante directamente al capital de las cuotas pendientes.
     * Reducir Cuota: recalcula cuota fija con mismo plazo.
     * Reducir Plazo: elimina cuotas desde el final hasta agotar el sobrante.
     */
    private function aplicarAbonoCapital(Credito $credito, float $sobrante, string $tipo, Carbon $hoy): void
    {
        $pendientes = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->orderBy('numero_cuota', 'asc')
            ->get();

        if ($pendientes->isEmpty()) return;

        // Primero descontamos el sobrante del capital de la primera cuota pendiente
        // y recalculamos el resto
        $nuevoCapitalTotal = round(
            $pendientes->sum(fn($c) => (float)$c->capital_esperado - (float)$c->capital_pagado) - $sobrante,
            2
        );

        if ($nuevoCapitalTotal <= 0) return;

        $tasaMensual = ($credito->tasa_interes_ordinario / 100) / 12;
        $numCuotas   = $pendientes->count();

        if ($tipo === 'Reducir Plazo') {
            // Eliminar cuotas desde el final hasta agotar el sobrante
            $resto = $sobrante;
            foreach ($pendientes->reverse() as $cuota) {
                if ($resto < 0.01) break;

                $capitalCuota = round((float)$cuota->capital_esperado - (float)$cuota->capital_pagado, 2);
                if ($resto >= $capitalCuota) {
                    $resto -= $capitalCuota;
                    $cuota->update([
                        'capital_pagado'           => $cuota->capital_esperado,
                        'interes_ordinario_pagado' => $cuota->interes_ordinario_esperado,
                        'pago_restante'            => 0,
                        'estado'                   => 'Pagado',
                        'fecha_ultimo_pago'        => $hoy,
                        'observaciones'            => 'Liquidada por abono anticipado a capital',
                    ]);
                } else {
                    // Abono parcial a esta cuota
                    $nuevoCapPagado = round((float)$cuota->capital_pagado + $resto, 2);
                    $cuota->update([
                        'capital_pagado' => $nuevoCapPagado,
                        'saldo_insoluto' => round(max(0, (float)$cuota->saldo_insoluto - $resto), 2),
                        'pago_restante'  => max(0, round(
                            ((float)$cuota->capital_esperado + (float)$cuota->interes_ordinario_esperado)
                            - ($nuevoCapPagado + (float)$cuota->interes_ordinario_pagado),
                            2
                        )),
                        'estado' => 'Parcial',
                    ]);
                    $resto = 0;
                }
            }
        } else {
            // Reducir Cuota: nueva cuota fija con el capital restante y mismos períodos
            $nuevaCuotaFija = ($tasaMensual > 0 && $numCuotas > 0)
                ? $nuevoCapitalTotal * ($tasaMensual / (1 - pow(1 + $tasaMensual, -$numCuotas)))
                : ($numCuotas > 0 ? $nuevoCapitalTotal / $numCuotas : 0);

            $saldoRestante = $nuevoCapitalTotal;

            foreach ($pendientes as $i => $cuota) {
                $esFinal    = ($i === $pendientes->count() - 1);
                $interesMes = round($saldoRestante * $tasaMensual, 2);
                $capitalMes = $esFinal
                    ? round($saldoRestante, 2)
                    : round($nuevaCuotaFija - $interesMes, 2);

                $cuota->update([
                    'saldo_insoluto'             => round($saldoRestante, 2),
                    'capital_esperado'           => $capitalMes,
                    'interes_ordinario_esperado' => $interesMes,
                    'cuota_fija'                 => round($capitalMes + $interesMes, 2),
                    'pago_restante'              => max(0, round(
                        ($capitalMes + $interesMes)
                        - ((float)$cuota->capital_pagado + (float)$cuota->interes_ordinario_pagado),
                        2
                    )),
                    // Preservar capital_pagado e interes_ordinario_pagado existentes
                ]);

                $saldoRestante -= $capitalMes;
            }
        }
    }

    public function cancelar(Request $request, Pago $pago)
    {
        $validated = $request->validate([
            'motivo_cancelacion' => 'required|string|max:500',
        ]);

        abort_if($pago->cancelado, 422, 'Este pago ya fue cancelado.');

        DB::transaction(function () use ($pago, $validated) {
            $snapshot = collect($pago->snapshot_amortizaciones ?? []);

            if ($snapshot->isNotEmpty()) {
                // Restauración exacta: devuelve cada cuota a su estado previo al pago
                // (necesario cuando el pago reescribió la tabla: Reducir Cuota/Plazo o liquidación).
                foreach ($snapshot as $fila) {
                    $amortizacion = Amortizacion::where('credito_id', $pago->credito_id)
                        ->whereKey($fila['id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$amortizacion) continue;

                    $amortizacion->update([
                        'capital_esperado'           => $fila['capital_esperado'],
                        'interes_ordinario_esperado' => $fila['interes_ordinario_esperado'],
                        'cuota_fija'                 => $fila['cuota_fija'],
                        'saldo_insoluto'             => $fila['saldo_insoluto'],
                        'capital_pagado'             => $fila['capital_pagado'],
                        'interes_ordinario_pagado'   => $fila['interes_ordinario_pagado'],
                        'interes_moratorio_pagado'   => $fila['interes_moratorio_pagado'],
                        'interes_moratorio_generado' => $fila['interes_moratorio_generado'],
                        'moratorio_acumulado'        => $fila['moratorio_acumulado'],
                        'pago_restante'              => $fila['pago_restante'],
                        'estado'                     => $fila['estado'],
                        'fecha_ultimo_pago'          => $fila['fecha_ultimo_pago'] ?? null,
                    ]);
                }
            } else {
                // Fallback para pagos históricos sin snapshot: reversión incremental
                $cuotas = collect($pago->cuotas_cubiertas ?? []);

                foreach ($cuotas as $detalle) {
                    $amortizacion = Amortizacion::where('credito_id', $pago->credito_id)
                        ->where('numero_cuota', $detalle['cuota'])
                        ->lockForUpdate()
                        ->first();

                    if (!$amortizacion) continue;

                    $capRevertir = (float)($detalle['cap'] ?? 0);
                    $ordRevertir = (float)($detalle['int'] ?? 0);
                    $morRevertir = (float)($detalle['mor'] ?? 0);

                    $nuevoCapPagado = max(0, round((float)$amortizacion->capital_pagado - $capRevertir, 2));
                    $nuevoOrdPagado = max(0, round((float)$amortizacion->interes_ordinario_pagado - $ordRevertir, 2));
                    $nuevoMorPagado = max(0, round((float)$amortizacion->interes_moratorio_pagado - $morRevertir, 2));
                    $nuevoSaldoInsoluto = round((float)$amortizacion->saldo_insoluto + $capRevertir, 2);

                    $amortizacion->update([
                        'capital_pagado'           => $nuevoCapPagado,
                        'interes_ordinario_pagado' => $nuevoOrdPagado,
                        'interes_moratorio_pagado' => $nuevoMorPagado,
                        'saldo_insoluto'           => $nuevoSaldoInsoluto,
                        'pago_restante'            => round(
                            ((float)$amortizacion->capital_esperado + (float)$amortizacion->interes_ordinario_esperado)
                            - ($nuevoCapPagado + $nuevoOrdPagado),
                            2
                        ),
                        'estado'            => ($nuevoCapPagado <= 0 && $nuevoOrdPagado <= 0) ? 'Pendiente' : 'Parcial',
                        'fecha_ultimo_pago' => null,
                    ]);
                }
            }

            $pago->update([
                'cancelado'          => true,
                'cancelado_por'      => Auth::id(),
                'cancelado_at'       => now(),
                'motivo_cancelacion' => $validated['motivo_cancelacion'],
            ]);

            $credito = $pago->credito;
            // Recalcular estatus: siempre verificar cuotas pendientes/vencidas tras reversión
            $activasQuery = fn() => $credito->amortizaciones()
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia']);

            if ($activasQuery()->count() === 0) {
                $credito->update(['estatus' => 'Liquidado']);
            } elseif ($activasQuery()->where('fecha_vencimiento', '<', now())->exists()) {
                $credito->update(['estatus' => 'Moroso']);
            } else {
                $credito->update(['estatus' => 'Activo']);
            }
        });

        return back()->with('success', 'Pago cancelado y amortizaciones revertidas correctamente.');
    }

    public function recibo(Pago $pago)
    {
        $pago->load(['acreditado', 'cajero', 'credito.modalidad']);

        return Inertia::render('Pagos/Recibo', [
            'pago' => [
                'id'                 => $pago->id,
                'folio'              => $pago->folio,
                'fecha_pago'         => $pago->fecha_pago->format('Y-m-d'),
                'forma_pago'         => $pago->forma_pago,
                'referencia'         => $pago->referencia,
                'monto_recibido'     => (float)$pago->monto_recibido,
                'aplicado_mora'      => (float)$pago->aplicado_mora,
                'aplicado_ordinario' => (float)$pago->aplicado_ordinario,
                'aplicado_capital'   => (float)$pago->aplicado_capital,
                'cuotas_cubiertas'   => $pago->cuotas_cubiertas ?? [],
                'observaciones'      => $pago->observaciones,
                'registrado_por'     => $pago->cajero?->name ?? 'Sistema',
                'created_at'         => $pago->created_at->format('Y-m-d H:i'),
                'acreditado'         => [
                    'nombre_completo'  => $pago->acreditado?->nombre_completo,
                    'municipio'        => $pago->acreditado?->municipio,
                    'rfc'              => $pago->acreditado?->rfc,
                ],
                'credito' => [
                    'clave_contrato' => $pago->credito?->clave_contrato,
                    'modalidad'      => $pago->credito?->modalidad?->nombre,
                    'monto_otorgado' => (float)($pago->credito?->monto_otorgado ?? 0),
                ],
            ],
        ]);
    }

    public function recibosPdf(Pago $pago)
    {
        $pago->load(['acreditado', 'cajero', 'credito.modalidad']);
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.recibo-pago', ['pago' => $pago]);
        $pdf->setPaper('letter', 'portrait');
        return $pdf->download("Recibo_{$pago->folio}.pdf");
    }

    /**
     * Mora real de una cuota vencida (RO Cláusula 7a): días > 5 sobre saldo vencido.
     */
    private function moraFilaPara(Carbon $hoy, Carbon $vencimiento, float $saldoVencido, float $tasaDiaria): float
    {
        if ($hoy->gt($vencimiento)) {
            // Carbon 3: diffInDays() con signo; vencimiento como receptor da días positivos.
            $dias = (int) $vencimiento->diffInDays($hoy);
            if ($dias > 5) {
                return round($saldoVencido * $tasaDiaria * $dias, 2);
            }
        }
        return 0.0;
    }

    /**
     * Resumen de lo vencido sobre las cuotas pendientes para validar el pago.
     * - moraTotal: mora real (días > 5) de cuotas vencidas.
     * - interesVencido: interés pendiente de cuotas con vencimiento <= hoy.
     * - capitalPendiente: capital pendiente de todas las cuotas activas.
     * - costoEstarAlDia: mora + interés + capital de las cuotas vencidas.
     */
    private function resumenVencido(Collection $cuotas, Carbon $hoy, float $tasaDiaria): array
    {
        $moraTotal       = 0.0;
        $interesVencido  = 0.0;
        $capitalPendiente = 0.0;
        $costoEstarAlDia = 0.0;

        foreach ($cuotas as $cuota) {
            $capitalPend = round((float) $cuota->capital_esperado - (float) $cuota->capital_pagado, 2);
            $interesPend = round(
                (float) $cuota->interes_ordinario_esperado - (float) $cuota->interes_ordinario_pagado,
                2
            );
            $capitalPendiente += $capitalPend;

            $vencimiento = Carbon::parse($cuota->fecha_vencimiento)->startOfDay();
            if ($vencimiento->lte($hoy)) {
                $saldoVencido = round(max(0, (float) $cuota->saldo_insoluto - (float) $cuota->capital_pagado), 2);
                $mora         = $this->moraFilaPara($hoy, $vencimiento, $saldoVencido, $tasaDiaria);

                $moraTotal       += $mora;
                $interesVencido  += $interesPend;
                $costoEstarAlDia += $mora + $interesPend + $capitalPend;
            }
        }

        return [
            'moraTotal'        => round($moraTotal, 2),
            'interesVencido'   => round($interesVencido, 2),
            'capitalPendiente' => round($capitalPendiente, 2),
            'costoEstarAlDia'  => round($costoEstarAlDia, 2),
        ];
    }
}
