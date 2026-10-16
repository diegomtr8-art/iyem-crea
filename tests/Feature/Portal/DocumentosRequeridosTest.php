<?php

use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;

/**
 * D.14: documentos requeridos y garantías permitidas según las Reglas de
 * Operación (Arts. 9, 10, 17, 18, 25 y 26). Una prueba por modalidad en cada
 * regla.
 */
function modalidadDocs(string $nombre): ModalidadCrea
{
    return ModalidadCrea::firstOrCreate(['nombre' => $nombre], [
        'tasa_interes'    => 10,
        'tasa_moratoria'  => 20,
        'monto_minimo'    => 1000,
        'monto_maximo'    => 1000000,
        'plazo_min_meses' => 3,
        'plazo_max_meses' => 24,
        'activo'          => true,
    ]);
}

/** Claves de documentos para una combinación, igual que las pide el wizard. */
function docsPara(string $modalidad, array $o = []): array
{
    $o += [
        'persona' => 'fisica', 'garantia' => 'aval', 'civil' => null,
        'civilAval' => null, 'monto' => 50000.0, 'mismoDomicilio' => false,
    ];

    return array_keys(DocumentoSolicitud::tiposRequeridos(
        modalidadDocs($modalidad)->id,
        $o['persona'], $o['garantia'], $o['civil'], $o['civilAval'], $o['monto'], $o['mismoDomicilio'],
    ));
}

/** Borrador de un ciudadano, ya con modalidad, para probar los pasos del wizard. */
function borradorDocs(string $modalidad, array $extra = []): array
{
    $mod  = modalidadDocs($modalidad);
    $user = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $solicitud = SolicitudCredito::create($extra + [
        'user_id'         => $user->id,
        'nombre_completo' => 'Carmen Xiu Balam',
        'modalidad_id'    => $mod->id,
        'estatus'         => 'Borrador',
    ]);

    return [$user, $solicitud, $mod];
}

dataset('modalidades docs', ['Artesanal', 'Emprendedores', 'Sustentable']);

// ─── Domicilio del negocio ────────────────────────────────────────────────────

test('pide el comprobante de domicilio del solicitante y del negocio; con "mismo domicilio" basta uno', function (string $modalidad) {
    expect(docsPara($modalidad))
        ->toContain('comprobante_domicilio', 'comprobante_domicilio_negocio');

    $mismo = docsPara($modalidad, ['mismoDomicilio' => true]);

    expect($mismo)->toContain('comprobante_domicilio')
        ->not->toContain('comprobante_domicilio_negocio');
})->with('modalidades docs');

// ─── Persona moral ────────────────────────────────────────────────────────────

test('persona moral no pide INE, CURP ni actas del solicitante y sí la documentación del representante', function (string $modalidad) {
    $moral = docsPara($modalidad, ['persona' => 'moral', 'civil' => 'Casado(a)']);

    expect($moral)
        ->not->toContain('ine_frente', 'ine_reverso', 'curp', 'acta_nacimiento', 'acta_matrimonio')
        ->toContain('id_rep_legal', 'acta_constitutiva', 'poder_rep_legal');

    // La persona física conserva INE, CURP y acta de nacimiento.
    expect(docsPara($modalidad))->toContain('ine_frente', 'ine_reverso', 'curp', 'acta_nacimiento');
})->with('modalidades docs');

// ─── Reglas propias de cada modalidad ─────────────────────────────────────────

dataset('reglas por modalidad', [
    // Art. 10: sin acta de matrimonio del solicitante, sin documento del bien del aval.
    'Artesanal' => ['Artesanal',
        ['constancia_artesano', 'acta_matrimonio_aval'],
        ['acta_matrimonio', 'doc_bien_aval', 'constancia_situacion']],
    // Art. 18: constancia fiscal y bien del aval; acta de matrimonio sí.
    'Emprendedores' => ['Emprendedores',
        ['constancia_situacion', 'doc_bien_aval', 'acta_matrimonio', 'acta_matrimonio_aval'],
        ['escritura_hipotecaria']],
    // Art. 26: constancia fiscal nueva y bien del aval; sin escritura por monto.
    'Sustentable' => ['Sustentable',
        ['constancia_situacion', 'doc_bien_aval', 'acta_matrimonio', 'acta_matrimonio_aval'],
        ['escritura_hipotecaria']],
]);

test('persona física casada con aval pide y omite lo que marca cada artículo', function (string $modalidad, array $pide, array $omite) {
    $docs = docsPara($modalidad, ['civil' => 'Casado(a)', 'civilAval' => 'Casado(a)', 'monto' => 300000.0]);

    expect($docs)->toContain(...$pide)->not->toContain(...$omite);
})->with('reglas por modalidad');

test('Sustentable pide la escritura solo con garantía hipotecaria, en un único documento', function () {
    $hipotecaria = docsPara('Sustentable', ['garantia' => 'hipotecaria', 'monto' => 300000.0]);

    expect($hipotecaria)->toContain('doc_propiedad_inmueble')
        ->not->toContain('escritura_hipotecaria');

    // Con monto alto pero otra garantía, no se pide escritura.
    expect(docsPara('Sustentable', ['garantia' => 'prendaria', 'monto' => 300000.0]))
        ->not->toContain('escritura_hipotecaria', 'doc_propiedad_inmueble');
});

test('el catálogo de nombres conserva los documentos que persona moral dejó de pedir y los nuevos', function () {
    $etiquetas = DocumentoSolicitud::etiquetas();

    foreach (['ine_frente', 'curp', 'acta_nacimiento', 'acta_matrimonio', 'comprobante_domicilio_negocio', 'doc_bien_aval'] as $clave) {
        expect($etiquetas)->toHaveKey($clave);
    }
});

// ─── Garantías: el servidor rechaza lo que no permiten los artículos ──────────

dataset('garantias por modalidad', [
    'Artesanal'     => ['Artesanal',     ['aval'],                          ['prendaria', 'hipotecaria']],
    'Emprendedores' => ['Emprendedores', ['aval', 'prendaria'],             ['hipotecaria']],
    'Sustentable'   => ['Sustentable',   ['aval', 'prendaria', 'hipotecaria'], []],
]);

test('el paso 1 acepta solo las garantías de cada modalidad', function (string $modalidad, array $permitidas, array $rechazadas) {
    [$user, , $mod] = borradorDocs($modalidad);

    $paso1 = fn (string $garantia) => $this->actingAs($user)->postJson(route('portal.solicitar.paso'), [
        'paso'  => 1,
        'modo'  => 'envio',
        'datos' => ['modalidad_id' => $mod->id, 'tipo_persona' => 'fisica', 'tipo_garantia' => $garantia],
    ]);

    foreach ($permitidas as $garantia) {
        $paso1($garantia)->assertOk();
        expect($user->solicitudCredito->fresh()->tipo_garantia)->toBe($garantia);
    }

    foreach ($rechazadas as $garantia) {
        $paso1($garantia)->assertStatus(422)->assertJsonValidationErrors(['datos.tipo_garantia']);
    }
})->with('garantias por modalidad');

test('Sustentable solo acepta garantía hipotecaria con monto de $200,000 o más', function () {
    [$user] = borradorDocs('Sustentable', ['tipo_garantia' => 'hipotecaria']);

    $paso5 = fn ($monto) => $this->actingAs($user)->postJson(route('portal.solicitar.paso'), [
        'paso'  => 5,
        'modo'  => 'envio',
        'datos' => ['monto_solicitado' => $monto, 'plazo_meses' => 12],
    ]);

    $paso5(150000)->assertStatus(422)->assertJsonValidationErrors(['datos.monto_solicitado']);
    expect($user->solicitudCredito->fresh()->monto_solicitado)->toBeNull();

    $paso5(200000)->assertOk();
    expect((float) $user->solicitudCredito->fresh()->monto_solicitado)->toBe(200000.0);
});

// ─── La casilla "mismo domicilio" llega hasta Mi Expediente ───────────────────

test('al marcar "mi negocio opera en mi mismo domicilio" ya no se pide el comprobante del negocio', function () {
    [$user] = borradorDocs('Emprendedores', ['tipo_garantia' => 'aval']);

    $guardarPaso4 = fn (bool $mismo) => $this->actingAs($user)->postJson(route('portal.solicitar.paso'), [
        'paso'  => 4,
        'modo'  => 'borrador',
        'datos' => ['nombre_comercial' => 'Panadería Xiu', 'giro_comercial' => 'Panadería', 'mismo_domicilio' => $mismo],
    ])->assertOk();

    $guardarPaso4(false);
    $this->actingAs($user)->get('/mi-portal/expediente')
        ->assertInertia(fn ($page) => $page->has('tipos_documentos.comprobante_domicilio_negocio'));

    $guardarPaso4(true);
    $this->actingAs($user)->get('/mi-portal/expediente')
        ->assertInertia(fn ($page) => $page
            ->has('tipos_documentos.comprobante_domicilio')
            ->missing('tipos_documentos.comprobante_domicilio_negocio'));
});
