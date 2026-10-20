<?php

namespace App\Services\Auditoria;

interface Comprobacion
{
    public function clave(): string;
    public function nombre(): string;
    public function ejecutar(): ResultadoComprobacion;
}

