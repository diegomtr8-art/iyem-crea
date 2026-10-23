<?php

use App\Mail\ContactoMensaje;
use App\Mail\MensajeCiudadanoMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

test('con el teléfono vacío el portal comparte un teléfono vacío', function () {
    config(['contacto.telefono' => '']);
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($user)->get(route('portal.contacto'))
        ->assertInertia(fn (Assert $page) => $page->where('contacto.telefono', ''));
});

test('con el teléfono definido el portal lo comparte', function () {
    config(['contacto.telefono' => '999 000 0000']);
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($user)->get(route('portal.contacto'))
        ->assertInertia(fn (Assert $page) => $page->where('contacto.telefono', '999 000 0000'));
});

test('sin configuración el correo de contacto es crea@iyemyucatan.com y el horario está vacío', function () {
    expect(config('contacto.correo'))->toBe('crea@iyemyucatan.com')
        ->and(config('contacto.horario'))->toBe('');
});

test('el aviso del formulario del portal va al correo configurado', function () {
    Mail::fake();
    config(['contacto.correo' => 'aviso@example.com']);
    $user = User::factory()->create(['tipo' => 'ciudadano']);

    $this->actingAs($user)->post(route('portal.contacto.enviar'), [
        'asunto'  => 'Duda',
        'mensaje' => 'Necesito ayuda con mi pago.',
    ]);

    Mail::assertSent(MensajeCiudadanoMail::class, fn ($mail) => $mail->hasTo('aviso@example.com'));
});

test('el aviso del formulario público va al correo configurado', function () {
    Mail::fake();
    config(['contacto.correo' => 'aviso@example.com']);

    $this->post(route('contacto.enviar'), [
        'nombre'  => 'Ana',
        'email'   => 'ana@example.com',
        'asunto'  => 'Duda',
        'mensaje' => 'Necesito ayuda.',
    ]);

    Mail::assertSent(ContactoMensaje::class, fn ($mail) => $mail->hasTo('aviso@example.com'));
});
