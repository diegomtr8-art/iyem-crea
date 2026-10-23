<?php

use Tests\Support\CiudadanosPortal;

/**
 * C-20: Mi Crédito para cada tipo de ciudadano.
 * Sin crédito registrado la pantalla redirige a Inicio (P-06).
 */
test('sin crédito registrado, Mi Crédito redirige a Inicio', function (string $tipo) {
    $user = CiudadanosPortal::crear($tipo);

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertRedirect(route('portal.dashboard'));
})->with(['sin solicitud', 'en revisión', 'aprobada sin crédito']);

test('crédito al corriente: la próxima cuota no tiene atraso', function () {
    $user = CiudadanosPortal::alCorriente();

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/MiCredito')
            ->where('credito.estatus', 'Activo')
            ->where('credito.proxima_cuota.numero', 2)
            ->where('credito.proxima_cuota.vencida', false)
            ->where('credito.proxima_cuota.dias_mora', 0));
});

test('crédito en mora: la próxima cuota está vencida y muestra días de atraso', function () {
    $user = CiudadanosPortal::enMora(diasAtraso: 20);

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/MiCredito')
            ->where('credito.estatus', 'Moroso')
            ->where('credito.proxima_cuota.numero', 1)
            ->where('credito.proxima_cuota.vencida', true)
            ->where('credito.proxima_cuota.dias_mora', 20));
});

test('atraso con crédito aún en estatus Activo: Mi Crédito igual muestra los días de atraso', function () {
    $user = CiudadanosPortal::enMora(diasAtraso: 20, estatus: 'Activo');

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('credito.estatus', 'Activo')
            ->where('credito.proxima_cuota.dias_mora', 20));
});

test('crédito liquidado: dice Liquidado y no hay próxima cuota', function () {
    $user = CiudadanosPortal::liquidado();

    $this->actingAs($user)
        ->get(route('portal.credito'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/MiCredito')
            ->where('credito.estatus', 'Liquidado')
            ->where('credito.proxima_cuota', null));
});
