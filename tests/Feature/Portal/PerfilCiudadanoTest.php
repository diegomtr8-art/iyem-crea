<?php

/**
 * C-20: Mi Perfil para cada tipo de ciudadano.
 *
 * La pantalla (portal.perfil) vive en feature/crea-portal-perfil, aún sin
 * fusionar a develop. Al fusionarla, reemplazar estos pendientes por pruebas
 * reales con Tests\Support\CiudadanosPortal.
 */
test('Mi Perfil: sin solicitud → redirige a la solicitud')
    ->todo();

test('Mi Perfil: con solicitud (en revisión, aprobada, con crédito) → 200 con teléfono y correo')
    ->todo();
