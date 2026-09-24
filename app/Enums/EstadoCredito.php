<?php

namespace App\Enums;

class EstadoCredito
{
    const ACTIVO = 'Activo';
    const LIQUIDADO = 'Liquidado';

    const MOROSO = 'Moroso';
    
    const CANCELADO = 'Cancelado';

    public static function todos(): array { return [
        self::ACTIVO,
        self::LIQUIDADO,
        self::MOROSO,
        self::CANCELADO,
    ]; }
}