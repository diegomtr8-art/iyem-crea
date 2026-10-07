<?php

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\Desembolso;
use App\Models\ModalidadCrea;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function crearDesembolsoConComprobante(): Desembolso
{
    $modalidad = ModalidadCrea::create([
        'nombre' => 'Modalidad-Test-' . uniqid(),
        'tasa_interes' => 7.00,
        'tasa_moratoria' => 17.50,
    ]);

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Acreditado Prueba',
        'municipio' => 'Mérida',
    ]);

    $credito = $acreditado->creditos()->create([
        'modalidad_id' => $modalidad->id,
        'clave_contrato' => 'DES-' . uniqid(),
        'monto_otorgado' => 10000,
        'plazo_meses' => 12,
        'fecha_entrega' => now()->toDateString(),
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus' => 'Activo',
    ]);

    $registrador = User::factory()->create([
        'tipo' => 'operativo',
        'email_verified_at' => now(),
    ]);

    return Desembolso::create([
        'credito_id' => $credito->id,
        'fecha_desembolso' => now()->toDateString(),
        'monto_desembolsado' => 10000,
        'forma_desembolso' => 'Transferencia',
        'registrado_por' => $registrador->id,
        'comprobante_ruta' => 'desembolso/comprobante.pdf',
    ]);
}

test('usuario sin sesion no puede acceder al comprobante de desembolso', function () {
    Storage::fake('local');

    $desembolso = crearDesembolsoConComprobante();

    Storage::disk('local')->put(
        $desembolso->comprobante_ruta,
        'contenido de prueba'
    );

    $response = $this->get(
        route('desembolso.comprobante', $desembolso)
    );

    expect(in_array($response->status(), [302, 403]))->toBeTrue();
});

test('usuario operativo puede descargar el comprobante de desembolso', function () {
    Storage::fake('local');

    $user = User::factory()->create([
        'tipo' => 'operativo',
        'email_verified_at' => now(),
    ]);

    $desembolso = crearDesembolsoConComprobante();

    Storage::disk('local')->put(
        $desembolso->comprobante_ruta,
        'contenido de prueba'
    );

    $response = $this
        ->actingAs($user)
        ->get(route('desembolso.comprobante', $desembolso));

    $response->assertOk();
});