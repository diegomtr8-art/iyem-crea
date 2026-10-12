<?php

/**
 * C-20: Avisos para cada tipo de ciudadano.
 *
 * La pantalla (portal.avisos) vive en feature/crea-portal-avisos, aún sin
 * fusionar a develop. Al fusionarla, reemplazar estos pendientes por pruebas
 * reales con Tests\Support\CiudadanosPortal.
 */
test('Avisos: sin solicitud, en revisión y aprobada sin crédito → sin cuotas')
    ->todo();

test('Avisos: crédito al corriente → cuotas próximas o pendientes')
    ->todo();

test('Avisos: crédito en mora → cuota vencida')
    ->todo();

test('Avisos: crédito liquidado → sin cuotas pendientes')
    ->todo();
