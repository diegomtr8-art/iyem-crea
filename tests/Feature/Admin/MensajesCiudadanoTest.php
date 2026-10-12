<?php

use App\Models\MensajeCiudadano;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function adminDeMensajes(): User
{
    $admin = User::factory()->create(['tipo' => 'operativo']);
    $admin->assignRole('Administrador');

    return $admin;
}

function mensajeDe(User $user, array $extra = []): MensajeCiudadano
{
    return $user->mensajes()->create(array_merge([
        'asunto'  => 'Duda de pago',
        'mensaje' => "Primera línea\nSegunda línea",
    ], $extra));
}

beforeEach(function () {
    Role::create(['name' => 'Administrador']);
});

test('el administrador ve la lista con lo más nuevo primero y el conteo de pendientes', function () {
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano', 'name' => 'Ana Pérez', 'email' => 'ana@example.com']);

    $viejo = mensajeDe($ciudadano, ['asunto' => 'Viejo']);
    $viejo->forceFill(['created_at' => Carbon::now()->subDays(2)])->save();
    $nuevo = mensajeDe($ciudadano, ['asunto' => 'Nuevo']);
    mensajeDe($ciudadano, ['asunto' => 'Atendido', 'atendido_at' => now()]);

    $this->actingAs(adminDeMensajes())
        ->get(route('mensajes-ciudadanos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/MensajesCiudadano/Index')
            ->where('pendientes', 2)
            ->has('mensajes.data', 3)
            ->where('mensajes.data.2.asunto', 'Viejo')
            ->where('mensajes.data.0.ciudadano', 'Ana Pérez')
            ->where('mensajes.data.0.correo', 'ana@example.com'));
});

test('el filtro separa pendientes de atendidos', function () {
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano']);
    mensajeDe($ciudadano, ['asunto' => 'Pendiente']);
    mensajeDe($ciudadano, ['asunto' => 'Resuelto', 'atendido_at' => now()]);

    $admin = adminDeMensajes();

    $this->actingAs($admin)->get(route('mensajes-ciudadanos.index', ['estado' => 'pendientes']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('mensajes.data', 1)
            ->where('mensajes.data.0.asunto', 'Pendiente'));

    $this->actingAs($admin)->get(route('mensajes-ciudadanos.index', ['estado' => 'atendidos']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('mensajes.data', 1)
            ->where('mensajes.data.0.asunto', 'Resuelto'));
});

test('el administrador abre el mensaje completo', function () {
    $mensaje = mensajeDe(User::factory()->create(['tipo' => 'ciudadano']));

    $this->actingAs(adminDeMensajes())
        ->get(route('mensajes-ciudadanos.show', $mensaje))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/MensajesCiudadano/Show')
            ->where('mensaje.mensaje', "Primera línea\nSegunda línea")
            ->where('mensaje.atendido', false));
});

test('un operativo sin rol de administrador recibe prohibido', function () {
    $mensaje = mensajeDe(User::factory()->create(['tipo' => 'ciudadano']));
    $operativo = User::factory()->create(['tipo' => 'operativo']);

    $this->actingAs($operativo)->get(route('mensajes-ciudadanos.index'))->assertForbidden();
    $this->actingAs($operativo)->get(route('mensajes-ciudadanos.show', $mensaje))->assertForbidden();
    $this->actingAs($operativo)->post(route('mensajes-ciudadanos.atendido', $mensaje))->assertForbidden();

    expect($mensaje->fresh()->atendido_at)->toBeNull();
});

test('un ciudadano no entra y un visitante va al login', function () {
    $ciudadano = User::factory()->create(['tipo' => 'ciudadano']);
    $mensaje = mensajeDe($ciudadano);

    $this->actingAs($ciudadano)->get(route('mensajes-ciudadanos.index'))->assertRedirect(route('portal.dashboard'));
    $this->actingAs($ciudadano)->post(route('mensajes-ciudadanos.atendido', $mensaje))->assertRedirect(route('portal.dashboard'));
    expect($mensaje->fresh()->atendido_at)->toBeNull();

    auth()->logout();
    $this->get(route('mensajes-ciudadanos.index'))->assertRedirect(route('login'));
});

test('marcar como atendido guarda quién y cuándo, y no se sobrescribe', function () {
    $mensaje = mensajeDe(User::factory()->create(['tipo' => 'ciudadano']));
    $primero = adminDeMensajes();
    $segundo = adminDeMensajes();

    $this->travelTo(Carbon::parse('2026-10-09 15:00', 'UTC'));
    $this->actingAs($primero)->post(route('mensajes-ciudadanos.atendido', $mensaje))->assertSessionHas('success');

    $mensaje->refresh();
    expect($mensaje->atendido_por)->toBe($primero->id)
        ->and($mensaje->atendido_at->equalTo(Carbon::parse('2026-10-09 15:00', 'UTC')))->toBeTrue();

    $this->travelTo(Carbon::parse('2026-10-10 15:00', 'UTC'));
    $this->actingAs($segundo)->post(route('mensajes-ciudadanos.atendido', $mensaje));

    expect($mensaje->fresh()->atendido_por)->toBe($primero->id)
        ->and($mensaje->fresh()->atendido_at->equalTo(Carbon::parse('2026-10-09 15:00', 'UTC')))->toBeTrue();
});

test('el contador del menú solo lo recibe el administrador', function () {
    mensajeDe(User::factory()->create(['tipo' => 'ciudadano']));

    $this->actingAs(adminDeMensajes())->get(route('mensajes-ciudadanos.index'))
        ->assertInertia(fn (Assert $page) => $page->where('mensajes_pendientes', 1));
});
