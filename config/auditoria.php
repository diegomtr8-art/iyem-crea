<?php

return [
    'comprobaciones'=>[
        \App\Services\Auditoria\ConciliacionPagos::class,
        \App\Services\Auditoria\MoraDesincronizada::class,
        \App\Services\Auditoria\IntegridadPlan::class,
    ]

    
];
