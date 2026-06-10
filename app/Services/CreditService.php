<?php

namespace App\Services;

use App\Models\Amortizacion;
use Carbon\Carbon;

class CreditService
{
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