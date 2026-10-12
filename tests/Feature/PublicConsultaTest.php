<?php

use App\Models\Acreditado;
use App\Models\ModalidadCrea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Actividad 6.10 — Consulta pública y verificación de CURP (C-12, C-13, C-14).
 * Todos los datos son de prueba, No se usan CURP de personas reales.
 */
function mensajeUnicoConsulta(): string
{
    return 'No encontramos un crédito con esos datos. Verifica tu CURP y tu número de contrato.';
}

function crearAcreditadoPrueba(string $curp, ?string $contrato = null, string $estatus = 'Activo'): Acreditado
{
    // El observer de créditos registra auditoría, por eso se crea con un usuario en sesión.
    Auth::login(User::factory()->create(['tipo' => 'operativo', 'email_verified_at' => now()]));

    $acreditado = Acreditado::create([
        'nombre_completo' => 'Prueba Consulta Publica',
        'curp' => $curp,
        'rfc' => substr($curp, 0, 10).'AB1',
        'municipio' => 'Mérida',
        'direccion_fiscal' => 'Calle 60 Secreta 123',
    ]);

    if ($contrato !== null) {
        $modalidad = ModalidadCrea::create([
            'nombre' => 'Artesanal-'.uniqid(),
            'tasa_interes' => 0.00,
            'tasa_moratoria' => 0.00,
        ]);

        $credito = $acreditado->creditos()->create([
            'modalidad_id' => $modalidad->id,
            'clave_contrato' => $contrato,
            'monto_otorgado' => 6000,
            'plazo_meses' => 3,
            'fecha_entrega' => Carbon::today()->toDateString(),
            'tasa_interes_ordinario' => 0,
            'tasa_interes_moratorio' => 0,
            'estatus' => $estatus,
        ]);

        $saldo = 6000;
        foreach ([2000, 2000, 2000] as $i => $monto) {
            $credito->amortizaciones()->create([
                'numero_cuota' => $i + 1,
                'fecha_vencimiento' => Carbon::today()->addMonths($i)->toDateString(),
                'saldo_insoluto' => $saldo,
                'capital_esperado' => $monto,
                'interes_ordinario_esperado' => 0,
                'cuota_fija' => $monto,
                'pago_restante' => $monto,
                'estado' => 'Pendiente',
                'capital_pagado' => 0,
                'interes_ordinario_pagado' => 0,
                'interes_moratorio_pagado' => 0,
                'interes_moratorio_generado' => 0,
                'moratorio_acumulado' => 0,
            ]);
            $saldo -= $monto;
        }
    }

    Auth::logout(); // la consulta pública la usa un visitante sin sesión

    return $acreditado;
}

// (a) El sexto intento en un minuto recibe 429
test('el sexto intento de consulta en un minuto recibe 429', function () {
    for ($i = 1; $i <= 5; $i++) {
        $respuesta = $this->post('/consultar-credito', [
            'curp' => 'PRUE900101HYNABC01',
            'clave_contrato' => 'NO-EXISTE-'.$i,
        ]);
        expect($respuesta->getStatusCode())->not->toBe(429);
    }

    $this->post('/consultar-credito', [
        'curp' => 'PRUE900101HYNABC01',
        'clave_contrato' => 'NO-EXISTE-6',
    ])->assertStatus(429);
});

// (b) CURP + RFC pero sin número de contrato: ya no muestra nada
test('con CURP y RFC pero sin número de contrato no se muestra nada', function () {
    crearAcreditadoPrueba('PRUE900101HYNABC01', 'CTR-6-10-A');

    $respuesta = $this->withHeaders(['X-Inertia' => 'true'])->post('/consultar-credito', [
        'curp' => 'PRUE900101HYNABC01',
        'rfc' => 'PRUE900101AB1',
    ]);

    $respuesta->assertSessionHasErrors('clave_contrato');
    expect($respuesta->getContent())->not->toContain('Prueba Consulta Publica');
});

// (c) Acreditado sin crédito: mensaje único y nunca error 500
test('un acreditado sin crédito recibe el mensaje único y no error 500', function () {
    crearAcreditadoPrueba('PRUE900101HYNABC01');

    $respuesta = $this->post('/consultar-credito', [
        'curp' => 'PRUE900101HYNABC01',
        'rfc' => 'PRUE900101AB1',
        'clave_contrato' => 'CTR-QUE-NO-EXISTE',
    ]);

    expect($respuesta->getStatusCode())->not->toBe(500);
    $respuesta->assertSessionHas('error', mensajeUnicoConsulta());
});

// (d) La respuesta no incluye domicilio ni otros datos personales
test('la respuesta de la consulta no incluye domicilio ni datos personales', function () {
    crearAcreditadoPrueba('PRUE900101HYNABC01', 'CTR-6-10-A');

    $respuesta = $this->withHeaders(['X-Inertia' => 'true'])->post('/consultar-credito', [
        'curp' => 'PRUE900101HYNABC01',
        'rfc' => 'PRUE900101AB1',
        'clave_contrato' => 'CTR-6-10-A',
    ]);

    $respuesta->assertOk();
    $datos = $respuesta->json('props.acreditado');
    expect($datos)->toBeArray();

    foreach (['domicilio', 'rfc', 'municipio', 'nombre', 'curp'] as $campo) {
        expect($datos)->not->toHaveKey($campo);
    }
    expect($respuesta->getContent())->not->toContain('Calle 60 Secreta');
});

// (e) verificarCurp responde igual para una CURP ajena con crédito y una inexistente
test('verificarCurp responde igual para una CURP ajena con crédito y una inexistente', function () {
    crearAcreditadoPrueba('PRUB900101MYNDEF02', 'CTR-6-10-B', 'Liquidado');
    $usuario = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $ajena = $this->actingAs($usuario)
        ->postJson(route('portal.solicitar.verificar-curp'), ['curp' => 'PRUB900101MYNDEF02']);
    $inexistente = $this->actingAs($usuario)
        ->postJson(route('portal.solicitar.verificar-curp'), ['curp' => 'PRUC900101HYNGHI03']);

    $ajena->assertOk();
    $inexistente->assertOk();
    expect($ajena->json())->toBe($inexistente->json());
    expect($ajena->json('mensaje'))->toBe('Verificamos tu CURP');
});
