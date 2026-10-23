<?php

namespace App\Services\Auditoria;

class ResultadoComprobacion
{
    public function __construct(
        public readonly string $clave,
        public readonly string $nombre,
        public readonly int $revisados,
        public readonly string $unidad,
        public readonly int $hallazgos,
        public readonly ?float $monto = null,
        public readonly array $detalle = [],
    ) {}

    public function tieneHallazgos(): bool
    {
        return $this->hallazgos >0;
    }
    public function toArray():array
    {
        return[
            'clave'=> $this->clave,
            'nombre'=> $this->nombre,
            'revisados'=> $this->revisados,
            'unidad'=> $this->unidad,
            'hallazgos'=> $this->hallazgos,
            'monto'=> $this->monto,
            'detalle' => array_map(fn ($fila) => (array) $fila, $this->detalle),
        ];
    }
}