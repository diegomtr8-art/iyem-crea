<?php

namespace App\Enums;

class EstadoCredito
{
    const ACTIVO = 'Activo';
    const LIQUIDADO = 'Liquidado';
    const VENCIDO = 'Vencido';
    const MOROSO = 'Moroso';
    
    //El estado cancelado aparece como comentario en la migración 2026_04_13_181455_create_creditos_table.php
    //const CANCELADO = 'Cancelado';

    public static function todos(): array { return [
        self::ACTIVO,
        self::LIQUIDADO,
        self::VENCIDO,
        self::MOROSO,
        //self::CANCELADO,
    ]; }
}