<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Inicio (dashboard) para cada tipo de ciudadano.
 * Se comprueba la prop que decide qué texto pinta Dashboard.vue.
 * Sin solicitud, `solicitud` es null; sin crédito, `credito_activo` es null.
 */
dataset('inicio', [
    'sin solicitud'        => ['sin solicitud', null, null],
    'en revisión'          => ['en revisión', 'En_Revision', null],
    'aprobada sin crédito' => ['aprobada sin crédito', 'Aprobada', null],
    'crédito al corriente' => ['crédito al corriente', 'Aprobada', 'Activo'],
    'crédito en mora'      => ['crédito en mora', 'Aprobada', 'Moroso'],
    'crédito liquidado'    => ['crédito liquidado', 'Aprobada', 'Liquidado'],
]);

test('Inicio responde 200 y muestra el estado del ciudadano', function (string $tipo, ?string $estatusSolicitud, ?string $estatusCredito) {
    $user = CiudadanosPortal::crear($tipo);

    $this->actingAs($user)
        ->get(route('portal.dashboard'))
        ->assertOk()
        ->assertInertia(function ($page) use ($estatusSolicitud, $estatusCredito) {
            $page->component('portal/Dashboard');

            $estatusSolicitud === null
                ? $page->where('solicitud', null)
                : $page->where('solicitud.estatus', $estatusSolicitud);

            $estatusCredito === null
                ? $page->where('credito_activo', null)
                : $page->where('credito_activo.estatus', $estatusCredito);
        });
})->with('inicio');
