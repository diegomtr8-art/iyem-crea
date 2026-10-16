<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Mi Perfil para cada tipo de ciudadano.
 *
 * La pantalla (portal.perfil) vive en feature/crea-portal-perfil, PR #17, aún
 * sin fusionar a develop. Al fusionarla, quitar el ->skip() y ajustar el cuerpo:
 *   sin solicitud → redirige a portal.solicitud.index con aviso
 *   con solicitud (cualquier otro tipo) → 200 con teléfono y correo
 */
test('Mi Perfil responde lo esperado para el ciudadano', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $response = $this->actingAs($user)->get(route('portal.perfil'));

    $tipo === 'sin solicitud'
        ? $response->assertRedirect(route('portal.solicitud.index'))
        : $response->assertOk();
})->with(CiudadanosPortal::TIPOS)->skip('Depende del PR #17 (portal.perfil aún no está en develop)');
