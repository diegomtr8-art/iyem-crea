<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Estado de cuenta (PDF) para cada tipo de ciudadano.
 */
test('sin crédito registrado, el estado de cuenta redirige a Inicio con aviso', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $this->actingAs($user)
        ->get(route('portal.credito.estado-cuenta.pdf'))
        ->assertRedirect(route('portal.dashboard'))
        ->assertSessionHas('info', 'Aún no tienes un crédito registrado.');
})->with(['sin solicitud', 'en revisión', 'aprobada sin crédito']);

test('con crédito, el estado de cuenta se descarga como PDF', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $response = $this->actingAs($user)
        ->get(route('portal.credito.estado-cuenta.pdf'))
        ->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
})->with(['crédito al corriente', 'crédito en mora', 'crédito liquidado']);
