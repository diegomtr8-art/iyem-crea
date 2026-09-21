<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\CiudadanoRegisterController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Google OAuth — requiere GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en .env
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');

    // NOTA DE SEGURIDAD (21-09-2026)
    // Aquí vivían GET/POST /register. Estaban dentro del grupo 'guest', es decir
    // abiertos a cualquiera, y RegisteredUserController::store creaba el usuario
    // sin asignar 'tipo', por lo que tomaba el valor por defecto de la tabla:
    // 'operativo'. Resultado: cualquiera podía darse de alta como operativo y,
    // con el único filtro que tiene el sistema (EsOperativo), condonar adeudos,
    // registrar o cancelar pagos y autorizar desembolsos.
    // La única protección era que ninguna vista enlazaba la ruta.
    // Se eliminan: la creación de usuarios operativos ya existe y está protegida
    // en POST /users (UserController@store, dentro de auth+verified+operativo).

    // Auto-registro para ciudadanos
    Route::get('ciudadano/registro', [CiudadanoRegisterController::class, 'create'])
        ->name('ciudadano.register');
    Route::post('ciudadano/registro', [CiudadanoRegisterController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
