<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Contacto está disponible para cualquier tipo de ciudadano.
 */
test('Contacto responde 200 para todos los tipos de ciudadano', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $this->actingAs($user)
        ->get(route('portal.contacto'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/Contacto'));
})->with(CiudadanosPortal::TIPOS);
