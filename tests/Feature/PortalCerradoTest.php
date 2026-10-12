<?php

use App\Models\User;

/**
 * Lanzamiento interno: con PORTAL_ABIERTO=false el portal del ciudadano
 * responde 404 y el personal sigue trabajando normal.
 */
dataset('rutas del portal', [
    'mi-portal'              => ['get', '/mi-portal'],
    'mi-portal/mi-credito'   => ['get', '/mi-portal/mi-credito'],
    'solicitud del portal'   => ['get', '/mi-portal/solicitud'],
    'registro GET'           => ['get', '/ciudadano/registro'],
    'registro POST'          => ['post', '/ciudadano/registro'],
    'google redirect'        => ['get', '/auth/google/redirect'],
    'google callback'        => ['get', '/auth/google/callback'],
    'consultar-credito GET'  => ['get', '/consultar-credito'],
    'consultar-credito POST' => ['post', '/consultar-credito'],
    'contacto público'       => ['post', '/contacto'],
]);

test('con el portal cerrado sus rutas responden 404', function (string $metodo, string $url) {
    config(['portal.abierto' => false]);

    $this->{$metodo}($url)->assertNotFound();
})->with('rutas del portal');

test('con el portal cerrado un ciudadano con sesión tampoco entra', function () {
    config(['portal.abierto' => false]);
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($ciudadano)->get('/mi-portal')->assertNotFound();
});

test('con el portal cerrado el personal entra a login, dashboard y recuperar contraseña', function () {
    config(['portal.abierto' => false]);

    $this->get('/login')->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/Login')->where('portalAbierto', false));
    $this->get('/forgot-password')->assertOk();
    $this->get('/')->assertOk();

    $operativo = User::factory()->create(['tipo' => 'operativo']);
    $this->post('/login', ['email' => $operativo->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->get('/dashboard')->assertOk();
    // Operativo: /solicitudes (bandeja del personal) no debe confundirse con el portal.
    $this->get('/solicitudes')->assertOk();
});

test('con el portal abierto todo funciona como antes', function () {
    config(['portal.abierto' => true]);

    $this->get('/login')->assertOk()
        ->assertInertia(fn ($page) => $page->where('portalAbierto', true));
    $this->get('/ciudadano/registro')->assertOk();
    $this->get('/consultar-credito')->assertRedirect('/');

    $ciudadano = User::factory()->create(['tipo' => 'ciudadano']);
    $this->actingAs($ciudadano)->get('/mi-portal')->assertOk();
});
