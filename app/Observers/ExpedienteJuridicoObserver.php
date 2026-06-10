<?php

namespace App\Observers;

use App\Models\AuditoriaLog;
use App\Models\ExpedienteJuridico;

class ExpedienteJuridicoObserver
{
    public function created(ExpedienteJuridico $exp): void
    {
        AuditoriaLog::registrar(
            'ExpedienteJuridico',
            $exp->id,
            'created',
            null,
            null,
            null,
            "Expediente jurídico creado. Crédito ID: {$exp->credito_id}. Monto reclamado: \${$exp->monto_reclamado}"
        );
    }

    public function updated(ExpedienteJuridico $exp): void
    {
        if ($exp->wasChanged('estatus')) {
            AuditoriaLog::registrar(
                'ExpedienteJuridico',
                $exp->id,
                'updated',
                'estatus',
                $exp->getOriginal('estatus'),
                $exp->estatus,
                "Cambio de estatus en expediente jurídico ID: {$exp->id}"
            );
        }
    }
}
