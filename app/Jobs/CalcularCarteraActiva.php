<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Sin ShouldQueue a propósito: en producción no hay worker de colas (solo schedule:run),
// así que el scheduler lo ejecuta directo en lugar de dejarlo esperando en la tabla jobs.
class CalcularCarteraActiva
{
    use Dispatchable;

    public const CLAVE_CACHE = 'dashboard.cartera_activa';

    public function handle(): void
    {
        // Lo que falta por pagar de las cuotas abiertas de los créditos vigentes
        $cuotasAbiertas = DB::table('amortizaciones')
            ->join('creditos', 'creditos.id', '=', 'amortizaciones.credito_id')
            ->whereIn('creditos.estatus', ['Activo', 'Moroso'])
            ->whereNotIn('amortizaciones.estado', ['Pagado', 'Condonado', 'Reestructurada']);

        // Sin expiración: si un día falla el cálculo, la tarjeta sigue mostrando
        // el último valor con su fecha, en lugar de quedarse vacía.
        Cache::put(self::CLAVE_CACHE, [
            'total'        => round((float) (clone $cuotasAbiertas)->sum('amortizaciones.pago_restante'), 2),
            'creditos'     => (clone $cuotasAbiertas)->distinct()->count('creditos.id'),
            'calculado_en' => now()->toIso8601String(),
        ]);
    }
}
