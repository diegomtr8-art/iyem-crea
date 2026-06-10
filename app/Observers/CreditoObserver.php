<?php

namespace App\Observers;

use App\Models\AuditoriaLog;
use App\Models\Credito;

class CreditoObserver
{
    public function created(Credito $credito): void
    {
        AuditoriaLog::registrar(
            'Credito',
            $credito->id,
            'created',
            null,
            null,
            null,
            "Crédito {$credito->clave_contrato} creado. Monto: \${$credito->monto_otorgado}"
        );
    }

    public function updated(Credito $credito): void
    {
        $campos = ['estatus', 'monto_otorgado', 'plazo_meses', 'tasa_interes_ordinario'];

        foreach ($campos as $campo) {
            if ($credito->wasChanged($campo)) {
                AuditoriaLog::registrar(
                    'Credito',
                    $credito->id,
                    'updated',
                    $campo,
                    $credito->getOriginal($campo),
                    $credito->getAttribute($campo),
                    "Modificación en crédito {$credito->clave_contrato}"
                );
            }
        }
    }
}
