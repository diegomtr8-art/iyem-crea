<?php

namespace App\Console\Commands;

use App\Models\Amortizacion;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateMoratorio extends Command
{
    protected $signature   = 'crea:update-moratorio';
    protected $description = 'Actualiza interes_moratorio_generado en cuotas vencidas según Reglas de Operación';

    public function handle(): void
    {
        $hoy = Carbon::now('America/Merida')->startOfDay();

        $pendientes = Amortizacion::with('credito')
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada'])
            ->where('fecha_vencimiento', '<', $hoy->toDateString())
            ->get();

        $actualizados = 0;

        foreach ($pendientes as $fila) {
            $credito     = $fila->credito;
            $tasaDiaria  = (((float)($credito->tasa_interes_moratorio ?? 0)) / 100) / 360;
            $vencimiento = Carbon::parse($fila->fecha_vencimiento)->startOfDay();
            $dias        = (int) $vencimiento->diffInDays($hoy);

            $mora = 0.0;
            if ($dias > 5) {
                $saldoVencido = round(max(0, (float)$fila->saldo_insoluto - (float)$fila->capital_pagado), 2);
                $mora = round($saldoVencido * $tasaDiaria * $dias, 2);
            }

            // Sincronizar ambos campos de mora para consistencia en portal y backoffice
            $fila->update([
                'interes_moratorio_generado' => $mora,
                'moratorio_acumulado'        => $mora,
            ]);
            $actualizados++;
        }

        $this->info("Moratorios actualizados: {$actualizados} cuotas.");
    }
}
