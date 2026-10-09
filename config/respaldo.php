<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Respaldo diario de la base (crea:respaldo-base)
    |--------------------------------------------------------------------------
    |
    | Carpeta donde se guardan los volcados. Nunca dentro de public/ ni de
    | storage/app/public. En el servidor conviene una carpeta fuera de
    | public_html; ver docs/RESPALDO.md.
    |
    */

    'directorio' => env('RESPALDO_DIRECTORIO', storage_path('app/private/respaldos')),

    // Ruta completa de mysqldump, por si el cron no lo encuentra en el PATH
    'mysqldump' => env('RESPALDO_MYSQLDUMP', 'mysqldump'),

];
