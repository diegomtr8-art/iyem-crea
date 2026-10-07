<?php

use App\Models\User;

/**
 * Errores claros: el portal ciudadano muestra su pantalla de error en español;
 * el panel operativo conserva las páginas de error de siempre.
 */
test('una ruta inexistente del portal muestra la pantalla de error en español con 404', function () {
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $this->actingAs($ciudadano)
        ->get('/mi-portal/no-existe')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('portal/Error')
            ->where('status', 404));
});

test('una ruta inexistente del portal pedida como JSON conserva su respuesta JSON', function () {
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $this->actingAs($ciudadano)
        ->getJson('/mi-portal/no-existe')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

test('una ruta inexistente del panel operativo no usa la pantalla del portal', function () {
    $operativo = User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]);

    $respuesta = $this->actingAs($operativo)->get('/dashboard/no-existe');

    $respuesta->assertNotFound();
    expect($respuesta->getContent())->not->toContain('portal\/Error')
        ->and($respuesta->getContent())->not->toContain('portal/Error');
});
