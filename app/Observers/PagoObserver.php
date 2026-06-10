<?php

namespace App\Observers;

use App\Models\AuditoriaLog;
use App\Models\Pago;

class PagoObserver
{
    public function created(Pago $pago): void
    {
        AuditoriaLog::registrar(
            'Pago',
            $pago->id,
            'created',
            null,
            null,
            null,
            "Pago {$pago->folio} registrado. Monto: \${$pago->monto_recibido} — Crédito ID: {$pago->credito_id}"
        );
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
                "Pago {$pago->folio} cancelado. Motivo: {$pago->motivo_cancelacion}"
            );
        }
    }
}
