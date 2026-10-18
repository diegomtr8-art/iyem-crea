<?php

use App\Mail\MensajeCiudadanoMail;
use App\Models\MensajeCiudadano;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('el ciudadano guarda un mensaje con su usuario de la sesión', function () {
    Mail::fake();
    $user  = User::factory()->create(['tipo' => 'ciudadano']);
    $otro  = User::factory()->create(['tipo' => 'ciudadano']);
    $texto = str_repeat('Contenido completo. ', 20);

    $this->actingAs($user)->post(route('portal.contacto.enviar'), [
        'asunto'  => 'Duda de pago',
        'mensaje' => $texto,
        'user_id' => $otro->id, // debe ignorarse
    ])->assertSessionHas('success');

    $m = MensajeCiudadano::sole();
    expect($m->user_id)->toBe($user->id)
        ->and($m->asunto)->toBe('Duda de pago')
        ->and($m->mensaje)->toBe($texto)
        ->and($m->created_at)->not->toBeNull();

    Mail::assertSent(MensajeCiudadanoMail::class, fn ($mail) => $mail->mensaje->is($m));
});

test('valida asunto y mensaje', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($user)->post(route('portal.contacto.enviar'), [])
        ->assertSessionHasErrors(['asunto', 'mensaje']);

    expect(MensajeCiudadano::count())->toBe(0);
});

test('el formulario se muestra al ciudadano', function () {
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($user)->get(route('portal.contacto'))->assertOk();
});

test('visitante y operativo no acceden', function () {
    $this->get(route('portal.contacto'))->assertRedirect(route('login'));

    $op = User::factory()->create(['tipo' => 'operativo']);
    $this->actingAs($op)->get(route('portal.contacto'))->assertRedirect(route('dashboard'));
});
