<?php
class EstadoCredito
{
    const ACTIVO = 'Activo';
    const LIQUIDADO = 'Liquidado';
    const VENCIDO = 'Vencido';
    const MOROSO = 'Moroso';
    const CANCELADO = 'Cancelado';

    public static function todos(): array { return [
        self::ACTIVO,
        self::LIQUIDADO,
        self::VENCIDO,
        self::MOROSO,
        self::CANCELADO,
    ]; }
}