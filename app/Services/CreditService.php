<?php

namespace App\Services;

use App\Models\Amortizacion;
use App\Models\Credito;
use Carbon\Carbon;

class CreditService
{
    /**
     * Genera la tabla de amortización de un crédito recién registrado.
     * Para modalidad Sustentable, los primeros 3 meses son período de gracia:
     * no se genera capital ni interés, solo se registra el transcurso del tiempo.
     */
    public function generarTablaAmortizacion(
        Credito $credito,
        float $monto,
        int $plazo,
        float $tasaOrdinariaAnual,
        Carbon $fechaInicial,
        string $modalidadNombre
    ): void {
        $tasaMensual   = ($tasaOrdinariaAnual / 100) / 12;
        $esSustentable = str_contains(strtolower($modalidadNombre), 'sustentable');
        $mesesGracia   = $esSustentable ? 3 : 0;

        for ($g = 1; $g <= $mesesGracia; $g++) {
            $credito->amortizaciones()->create([
                'numero_cuota'               => $g - $mesesGracia - 1,
                'fecha_vencimiento'          => $fechaInicial->copy()->addMonths($g)->format('Y-m-d'),
                'saldo_insoluto'             => round($monto, 2),
                'capital_esperado'           => 0,
                'interes_ordinario_esperado' => 0,
                'cuota_fija'                 => 0,
                'pago_restante'              => 0,
                'estado'                     => 'Gracia',
                'capital_pagado'             => 0,
                'interes_ordinario_pagado'   => 0,
                'interes_moratorio_pagado'   => 0,
                'interes_moratorio_generado' => 0,
                'observaciones'              => 'Período de gracia — sin pago requerido',
            ]);
        }

        $cuotaFija = ($tasaMensual > 0)
            ? $monto * ($tasaMensual / (1 - pow(1 + $tasaMensual, -$plazo)))
            : $monto / $plazo;

        $saldo = $monto;
        for ($i = 1; $i <= $plazo; $i++) {
            $interes = round($saldo * $tasaMensual, 2);
            $capital = ($i === $plazo) ? round($saldo, 2) : round($cuotaFija - $interes, 2);
            $cuota   = round($capital + $interes, 2);

            $credito->amortizaciones()->create([
                'numero_cuota'               => $i,
                'fecha_vencimiento'          => $fechaInicial->copy()->addMonths($i + $mesesGracia)->format('Y-m-d'),
                'saldo_insoluto'             => round($saldo, 2),
                'capital_esperado'           => $capital,
                'interes_ordinario_esperado' => $interes,
                'cuota_fija'                 => $cuota,
                'pago_restante'              => $cuota,
                'estado'                     => 'Pendiente',
                'capital_pagado'             => 0,
                'interes_ordinario_pagado'   => 0,
                'interes_moratorio_pagado'   => 0,
                'interes_moratorio_generado' => 0,
            ]);

            $saldo -= $capital;
        }
    }

    public function actualizarMoraDeCuota(Amortizacion $fila)
    {
        if ($fila->estado === 'Pagado') return $fila->interes_moratorio_pagado;

        $hoy = Carbon::now('America/Merida');
        $vencimiento = Carbon::parse($fila->fecha_vencimiento);
        $tasaMoratoriaAnual = $fila->credito->tasa_interes_moratorio;

        if ($hoy->gt($vencimiento)) {
            $diasAtraso = $hoy->diffInDays($vencimiento);

            // Regla de los 5 días de gracia
            if ($diasAtraso > 5) {
                // Año comercial 360
                $tasaDiaria = ($tasaMoratoriaAnual / 100) / 360;
                
                // Calculamos la mora sobre lo que falta pagar (pago_restante)
                $nuevaMora = round($fila->pago_restante * $tasaDiaria * $diasAtraso, 2);
                
                // Actualizamos en la base de datos para que sea "real"
                // Nota: Usamos una columna para mora acumulada si quieres persistirla
                $fila->update(['interes_moratorio_generado' => $nuevaMora]);
                
                return $nuevaMora;
            }
        }

        return 0;
    }
}