<?php

use App\Models\Acreditado;
use App\Models\SolicitudCredito;
use App\Models\User;

function ciudadanoConSolicitud(array $solicitud = []): array
{
    $user = User::factory()->create(['tipo' => 'ciudadano']);
    $sol = SolicitudCredito::create(array_merge([
        'user_id'  => $user->id,
        'estatus'  => 'Borrador',
        'telefono' => '9991234567',
        'correo'   => 'anterior@example.com',
        'curp'     => 'AAAA010101HYNXXX01',
        'rfc'      => 'AAAA010101AB1',
        'direccion' => 'Calle 1',
    ], $solicitud));

    return [$user, $sol];
}

test('la pantalla de perfil se muestra precargada', function () {
    [$user] = ciudadanoConSolicitud();

    $this->actingAs($user)
        ->get(route('portal.perfil'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/Perfil')
            ->where('perfil.telefono', '9991234567')
            ->where('perfil.correo', 'anterior@example.com'));
});

test('el ciudadano actualiza teléfono y correo y persiste', function () {
    [$user, $sol] = ciudadanoConSolicitud();

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => '9998887766', 'correo' => 'nuevo@example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('portal.perfil'))
        ->assertSessionHas('success');

    $this->actingAs($user)->get(route('portal.perfil'))
        ->assertInertia(fn ($page) => $page
            ->where('perfil.telefono', '9998887766')
            ->where('perfil.correo', 'nuevo@example.com'));

    expect($sol->refresh()->telefono)->toBe('9998887766');
});

test('no modifica CURP, RFC, domicilio ni users.email', function () {
    [$user, $sol] = ciudadanoConSolicitud();
    $emailLogin = $user->email;

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), [
            'telefono'  => '9998887766',
            'correo'    => 'nuevo@example.com',
            'curp'      => 'ZZZZ010101HYNXXX01',
            'rfc'       => 'ZZZZ010101ZZ9',
            'direccion' => 'Otra calle',
        ])->assertSessionHasNoErrors();

    $sol->refresh();
    expect($sol->curp)->toBe('AAAA010101HYNXXX01')
        ->and($sol->rfc)->toBe('AAAA010101AB1')
        ->and($sol->direccion)->toBe('Calle 1')
        ->and($user->refresh()->email)->toBe($emailLogin);
});

test('teléfono vacío se rechaza y no cambia el valor guardado', function () {
    [$user, $sol] = ciudadanoConSolicitud();

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => '', 'correo' => 'nuevo@example.com'])
        ->assertSessionHasErrors(['telefono' => 'El teléfono celular es obligatorio.']);

    expect($sol->refresh()->telefono)->toBe('9991234567')
        ->and($sol->correo)->toBe('anterior@example.com');
});

test('teléfono con formato inválido se rechaza', function (string $telefono) {
    [$user, $sol] = ciudadanoConSolicitud();

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => $telefono, 'correo' => 'nuevo@example.com'])
        ->assertSessionHasErrors(['telefono' => 'El teléfono celular debe tener 10 dígitos.']);

    expect($sol->refresh()->telefono)->toBe('9991234567');
})->with(['9 dígitos' => '999123456', 'con guiones' => '999-123-4567', 'con espacios' => '999 123 4567', 'letras' => '99912345ab']);

test('correo vacío o inválido se rechaza', function (string $correo) {
    [$user, $sol] = ciudadanoConSolicitud();

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => '9998887766', 'correo' => $correo])
        ->assertSessionHasErrors('correo');

    expect($sol->refresh()->correo)->toBe('anterior@example.com');
})->with(['vacío' => '', 'sin arroba' => 'no-es-correo']);

test('sincroniza el correo del acreditado cuando existe', function () {
    $acreditado = Acreditado::create(['nombre_completo' => 'Test Ciudadano', 'municipio' => 'Mérida', 'sexo' => 'H', 'correo' => 'anterior@example.com']);
    [$user] = ciudadanoConSolicitud(['acreditado_id' => $acreditado->id]);

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => '9998887766', 'correo' => 'nuevo@example.com'])
        ->assertSessionHasNoErrors();

    expect($acreditado->refresh()->correo)->toBe('nuevo@example.com');
});

test('sin solicitud redirige al wizard y no crea ninguna', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($user)->get(route('portal.perfil'))
        ->assertRedirect(route('portal.solicitud.index'));

    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => '9998887766', 'correo' => 'nuevo@example.com'])
        ->assertRedirect(route('portal.solicitud.index'));

    expect(SolicitudCredito::count())->toBe(0);
});

test('un usuario que no es ciudadano no accede', function () {
    $user = User::factory()->create(['tipo' => 'operativo']);

    $this->actingAs($user)->get(route('portal.perfil'))->assertRedirect()->assertSessionMissing('success');
    $this->actingAs($user)
        ->patch(route('portal.perfil.update'), ['telefono' => '9998887766', 'correo' => 'x@example.com'])
        ->assertRedirect();
});
