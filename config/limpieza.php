<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Limpieza de bitácoras viejas (crea:limpiar-bitacoras)
    |--------------------------------------------------------------------------
    |
    | Días que se conservan en cada tabla; lo más viejo se borra a diario.
    | Los plazos por omisión se acordaron con Diego en el ticket J.13.
    | auditoria_log no está aquí a propósito: nunca se borra.
    | Ver docs/TAREAS_PROGRAMADAS.md, sección 6.
    |
    */

    'bitacora_tareas_dias' => (int) env('LIMPIEZA_BITACORA_TAREAS_DIAS', 90),

    'correos_fallidos_dias' => (int) env('LIMPIEZA_CORREOS_FALLIDOS_DIAS', 30),

];
