<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Mi Perfil para cada tipo de ciudadano.
 *
 * La pantalla es portal.perfil (PR #17):
 *   sin solicitud → redirige a portal.solicitud.index con aviso
 *   con solicitud (cualquier otro tipo) → 200 con teléfono y correo
 */
test('Mi Perfil responde lo esperado para el ciudadano', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $response = $this->actingAs($user)->get(route('portal.perfil'));

    $tipo === 'sin solicitud'
        ? $response->assertRedirect(route('portal.solicitud.index'))
        : $response->assertOk();
})->with(CiudadanosPortal::TIPOS);
