<?php

return [
    // false = el portal del ciudadano queda cerrado (responde 404). El
    // personal (login, dashboard, panel operativo) no se ve afectado.
    'abierto' => (bool) env('PORTAL_ABIERTO', false),
];
