<?php

use App\Models\User;
use App\Models\SolicitudCredito;
use App\Http\Controllers\Operativo\SolicitudOperativoController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

/**
 * Fixture: solicitud de crédito para pruebas.
 */
function crearSolicitudFormatos(
    User $usuario,
    string $estatus,
    ?string $zipRuta = null
): SolicitudCredito {
    return SolicitudCredito::create([
        'user_id' => $usuario->id,
        'nombre_completo' => 'Usuario de Prueba',
        'curp' => 'GARC990215HYNRTL08',
        'municipio' => 'Mérida',
        'telefono' => '9991234567',
        'correo' => $usuario->email,
        'estatus' => $estatus,
        'formatos_zip_ruta' => $zipRuta,
    ]);
}

/**
 * Caso 1: una solicitud enviada no puede descargar
 * formatos, aunque exista un ZIP antiguo.
 */
test('solicitud enviada no permite descargar formatos', function () {
    Storage::fake('local');

    $usuario = User::factory()->create([
        'tipo' => 'ciudadano',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($usuario);

    $rutaZip = 'formatos/prueba.zip';
    Storage::disk('local')->put($rutaZip, 'Contenido de prueba');

    $solicitud = crearSolicitudFormatos(
        $usuario,
        'Enviada',
        $rutaZip
    );

    $this->get(route(
        'portal.solicitar.descargar-formatos',
        $solicitud->id
    ))->assertNotFound();

        expect($solicitud->fresh()->estatus)->toBe('Enviada');
    });

/**
 * Caso 2: una solicitud aprobada puede descargar
 * los formatos cuando el archivo existe.
 */
test('solicitud aprobada permite descargar formatos', function () {
    Storage::fake('local');

    $usuario = User::factory()->create([
        'tipo' => 'ciudadano',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($usuario);

    $rutaZip = 'formatos/prueba.zip';
    Storage::disk('local')->put($rutaZip, 'Contenido de prueba');

    $solicitud = crearSolicitudFormatos(
        $usuario,
        'Aprobada',
        $rutaZip
    );

    $this->get(route(
        'portal.solicitar.descargar-formatos',
        $solicitud->id
    ))
        ->assertOk()
        ->assertDownload("CREA_Formatos_{$solicitud->id}.zip");

    expect($solicitud->fresh()->estatus)->toBe('Aprobada');
});

/**
 * Caso 3: una solicitud aprobada sin ZIP
 * todavía no puede descargar los formatos.
 */
test('solicitud aprobada sin ZIP devuelve 404', function () {
    Storage::fake('local');

    $usuario = User::factory()->create([
        'tipo' => 'ciudadano',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($usuario);

    $solicitud = crearSolicitudFormatos(
        $usuario,
        'Aprobada'
    );

    $this->get(route(
        'portal.solicitar.descargar-formatos',
        $solicitud->id
    ))->assertNotFound();
});

/**
 * Caso 4: si falla la generación del ZIP,
 * la solicitud permanece aprobada.
 */
test('falla de ZIP no impide aprobar solicitud', function () {
    Storage::fake('local');
    Log::spy();

    $operativo = User::factory()->create([
        'tipo' => 'operativo',
        'email_verified_at' => now(),
    ]);

    $ciudadano = User::factory()->create([
        'tipo' => 'ciudadano',
        'email_verified_at' => now(),
    ]);

    $solicitud = crearSolicitudFormatos(
        $ciudadano,
        'En_Revision'
    );

    $this->actingAs($operativo);

    // Provocar un conflicto en la ruta de almacenamiento.
    $rutaFormatos = "solicitudes/{$solicitud->id}/formatos";

    Storage::disk('local')->put(
        $rutaFormatos,
        'Archivo que impide crear el directorio'
    );

    // El operativo intenta aprobar la solicitud.
    $this->post(route(
        'solicitudes.cambiar-estatus',
        $solicitud->id
    ), [
        'estatus' => 'Aprobada',
        'observaciones' => 'Solicitud aprobada',
    ])->assertRedirect();

    // La solicitud debe permanecer aprobada.
    expect($solicitud->fresh()->estatus)->toBe('Aprobada');

    // No debe registrarse un ZIP generado.
    expect($solicitud->fresh()->formatos_zip_ruta)->toBeNull();

    // Debe registrarse el error de generación.
    Log::shouldHaveReceived('error')
        ->atLeast()
        ->once()
        ->withArgs(fn ($mensaje) =>
            str_contains(
                $mensaje,
                'Error generando ZIP para solicitud ' . $solicitud->id
            )
        );
});
