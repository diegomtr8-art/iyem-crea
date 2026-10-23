<?php

namespace App\Observers;

use App\Models\AuditoriaLog;
use App\Models\Credito;
use DateTimeInterface;

class CreditoObserver
{
    // Campos que cambian dinero, estado o identidad del crédito
    private const CAMPOS = [
        'estatus'                 => 'estatus',
        'monto_otorgado'          => 'monto otorgado',
        'plazo_meses'             => 'plazo en meses',
        'tasa_interes_ordinario'  => 'tasa ordinaria',
        'tasa_interes_moratorio'  => 'tasa moratoria',
        'fecha_entrega'           => 'fecha de entrega',
        'fecha_contrato'          => 'fecha de contrato',
        'modalidad_id'            => 'modalidad',
        'acreditado_id'           => 'acreditado',
        'clave_contrato'          => 'clave de contrato',
        'numero_contrato_oficial' => 'número de contrato oficial',
        'folio_solicitud'         => 'folio de solicitud',
    ];

    public function created(Credito $credito): void
    {
        AuditoriaLog::registrar(
            'Credito',
            $credito->id,
            'created',
            'monto_otorgado',
            null,
            $credito->monto_otorgado,
            "Crédito {$credito->clave_contrato} creado. Monto: " . AuditoriaLog::dinero($credito->monto_otorgado)
            . ", plazo: {$credito->plazo_meses} meses, tasa ordinaria: {$credito->tasa_interes_ordinario} %"
            . ", tasa moratoria: {$credito->tasa_interes_moratorio} %."
        );
    }

    public function updated(Credito $credito): void
    {
        // Si cambió la clave, se nombra con la de antes para poder encontrar el historial
        $clave = $credito->getOriginal('clave_contrato');

        foreach (self::CAMPOS as $campo => $nombre) {
            if ($credito->wasChanged($campo)) {
                $anterior = $this->texto($credito->getOriginal($campo));
                $nuevo    = $this->texto($credito->getAttribute($campo));

                AuditoriaLog::registrar(
                    'Credito',
                    $credito->id,
                    'updated',
                    $campo,
                    $anterior,
                    $nuevo,
                    "Crédito {$clave}: {$nombre} cambió de " . ($anterior ?? 'vacío') . ' a ' . ($nuevo ?? 'vacío') . '.'
                );
            }
        }
    }

    private function texto(mixed $valor): ?string
    {
        return $valor instanceof DateTimeInterface ? $valor->format('Y-m-d') : ($valor === null ? null : (string) $valor);
    }
}
