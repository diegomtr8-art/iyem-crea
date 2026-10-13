<?php

return [
    'telefono' => env('CREA_CONTACTO_TELEFONO', ''),
    'correo'   => env('CREA_CONTACTO_CORREO') ?: 'crea@iyemyucatan.com',
    'horario'  => env('CREA_CONTACTO_HORARIO', ''),
];
