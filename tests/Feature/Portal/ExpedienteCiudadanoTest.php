<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Mi Expediente para cada tipo de ciudadano. Nunca redirige.
 */
test('sin solicitud, Mi Expediente responde 200 sin datos de solicitud', function () {
    $user = CiudadanosPortal::sinSolicitud();

    $this->actingAs($user)
        ->get(route('portal.expediente'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/Expediente')
            ->where('solicitud', null)
            ->where('documentos', null)
            ->has('tipos_documentos'));
});

test('con solicitud, Mi Expediente responde 200 con el estatus de la solicitud', function (string $tipo, string $estatus) {
    $user = CiudadanosPortal::crear($tipo);

    $this->actingAs($user)
        ->get(route('portal.expediente'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/Expediente')
            ->where('solicitud.estatus', $estatus)
            ->has('documentos')
            ->has('tipos_documentos'));
})->with([
    'en revisión'          => ['en revisión', 'En_Revision'],
    'aprobada sin crédito' => ['aprobada sin crédito', 'Aprobada'],
    'crédito al corriente' => ['crédito al corriente', 'Aprobada'],
    'crédito en mora'      => ['crédito en mora', 'Aprobada'],
    'crédito liquidado'    => ['crédito liquidado', 'Aprobada'],
]);
