<?php

namespace App\Observers;

use App\Models\AuditoriaLog;
use App\Models\Pago;

class PagoObserver
{
    public function created(Pago $pago): void
    {
        $clave = $pago->credito?->clave_contrato;
        $abono = in_array($pago->tipo_abono, ['Reducir Cuota', 'Reducir Plazo'], true)
            ? ' Sobrante de ' . AuditoriaLog::dinero($pago->sobrante_aplicado) . " aplicado a capital ({$pago->tipo_abono})."
            : '';

        AuditoriaLog::registrar(
            'Pago',
            $pago->id,
            'created',
            'monto_recibido',
            null,
            $pago->monto_recibido,
            "Pago {$pago->folio} registrado en el crédito {$clave}: " . AuditoriaLog::dinero($pago->monto_recibido)
            . " ({$pago->forma_pago}). Aplicado a mora " . AuditoriaLog::dinero($pago->aplicado_mora)
            . ', interés ' . AuditoriaLog::dinero($pago->aplicado_ordinario)
            . ', capital ' . AuditoriaLog::dinero($pago->aplicado_capital) . '.' . $abono
        );

        if ($abono !== '') {
            $this->registrarCambioDePlan($pago, $clave);
        }
    }

    public function updated(Pago $pago): void
    {
        if ($pago->wasChanged('cancelado')) {
            AuditoriaLog::registrar(
                'Pago',
                $pago->id,
                'updated',
                'cancelado',
                $pago->getOriginal('cancelado') ? 'Cancelado' : 'Activo',
                $pago->cancelado ? 'Cancelado' : 'Activo',
                "Pago {$pago->folio} de " . AuditoriaLog::dinero($pago->monto_recibido)
                . " del crédito {$pago->credito?->clave_contrato} cancelado. Motivo: {$pago->motivo_cancelacion}"
            );
        }
    }

    /**
     * Reducir cuota o plazo reescribe el plan sin cambiar ningún campo del crédito, así que el
     * CreditoObserver no lo ve. El "antes" sale del snapshot que el pago guarda para poder cancelarse.
     */
    private function registrarCambioDePlan(Pago $pago, ?string $clave): void
    {
        $antes = collect($pago->snapshot_amortizaciones ?? [])->sortBy('numero_cuota')->values();

        $despues = $pago->credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->orderBy('numero_cuota')
            ->get(['cuota_fija']);

        $plan = fn (int $cuotas, mixed $cuota) => "{$cuotas} cuotas pendientes, la siguiente de " . AuditoriaLog::dinero($cuota);

        AuditoriaLog::registrar(
            'Credito',
            $pago->credito_id,
            'updated',
            'plan_de_pagos',
            $plan($antes->count(), $antes->first()['cuota_fija'] ?? 0),
            $plan($despues->count(), $despues->first()?->cuota_fija ?? 0),
            "Crédito {$clave}: {$pago->tipo_abono} con el pago {$pago->folio}. Se abonaron "
            . AuditoriaLog::dinero($pago->sobrante_aplicado) . ' a capital. El antes cuenta la cuota que cubrió este pago.'
        );
    }
}
