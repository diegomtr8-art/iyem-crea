<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;

/**
 * D.18: correos de verificación de cuenta y restablecimiento de contraseña.
 * Asunto, saludo con el nombre y enlace con el dominio de APP_URL.
 */
test('verificación: asunto en español, saludo con el nombre y enlace con APP_URL', function () {
    config(['app.url' => 'https://crea.ejemplo.test']);
    $user = User::factory()->unverified()->create(['name' => 'Prueba Correos']);

    $mail = (new VerifyEmail)->toMail($user);

    expect($mail->subject)->toBe('Verifica tu correo electrónico — CREA')
        ->and($mail->greeting)->toBe('¡Bienvenido a CREA, Prueba Correos!')
        ->and($mail->actionUrl)->toStartWith('https://crea.ejemplo.test/verify-email/' . $user->id . '/')
        ->and($mail->actionUrl)->not->toContain('localhost')
        ->and($mail->actionUrl)->not->toContain('127.0.0.1');
});

test('verificación: el enlace del correo verifica la cuenta', function () {
    config(['app.url' => 'http://localhost']);
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmail)->toMail($user);

    $this->actingAs($user)->get($mail->actionUrl)->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('restablecer: asunto en español, saludo con el nombre y enlace con APP_URL', function () {
    config(['app.url' => 'https://crea.ejemplo.test']);
    $user = User::factory()->create(['name' => 'Prueba Correos']);

    $mail = (new ResetPassword('token-de-prueba'))->toMail($user);

    expect($mail->subject)->toBe('Recupera tu contraseña — CREA')
        ->and($mail->greeting)->toBe('¡Hola, Prueba Correos!')
        ->and($mail->actionUrl)->toStartWith('https://crea.ejemplo.test/reset-password/token-de-prueba?email=')
        ->and($mail->actionUrl)->toContain(urlencode($user->email))
        ->and($mail->actionUrl)->not->toContain('localhost');
});

test('sin nombre, el saludo queda genérico', function () {
    $user = User::factory()->create(['name' => '']);

    expect((new ResetPassword('t'))->toMail($user)->greeting)->toBe('¡Hola!')
        ->and((new VerifyEmail)->toMail($user)->greeting)->toBe('¡Bienvenido a CREA!');
});

test('la plantilla .env.example deja el idioma de la app en español', function () {
    $env = file_get_contents(base_path('.env.example'));

    expect($env)->toMatch('/^APP_LOCALE=es\s*$/m')
        ->and($env)->toMatch('/^APP_FALLBACK_LOCALE=es\s*$/m');
});
