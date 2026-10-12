<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Avisos para cada tipo de ciudadano.
 *
 * La pantalla (portal.avisos) vive en feature/crea-portal-avisos, PR #23, aún
 * sin fusionar a develop. Al fusionarla, quitar el ->skip() y completar el
 * cuerpo con el dato clave de cada tipo:
 *   sin solicitud / en revisión / aprobada sin crédito → credito null, cuotas []
 *   crédito al corriente → cuotas próximas o pendientes
 *   crédito en mora      → cuota vencida
 *   crédito liquidado    → sin cuotas pendientes
 */
test('Avisos responde 200 y muestra el dato clave del ciudadano', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $this->actingAs($user)
        ->get(route('portal.avisos'))
        ->assertOk();
})->with(CiudadanosPortal::TIPOS)->skip('Depende del PR #23 (portal.avisos aún no está en develop)');
