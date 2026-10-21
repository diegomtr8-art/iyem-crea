<?php

use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\SolicitudCredito;
use App\Models\User;

// Mi Expediente debe esperar los mismos documentos que el wizard de solicitud
// (modalidad + aval incluidos) y mostrarlos con su nombre legible (P-12, C-09).

function crearSolicitudCiudadano(string $modalidad, ?string $garantia = 'aval'): array
{
    $mod = ModalidadCrea::create([
        'nombre'          => $modalidad,
        'tasa_interes'    => 10,
        'tasa_moratoria'  => 20,
        'monto_minimo'    => 1000,
        'monto_maximo'    => 100000,
        'plazo_min_meses' => 3,
        'plazo_max_meses' => 24,
        'activo'          => true,
    ]);

    $user = User::factory()->create(['tipo' => 'ciudadano', 'email_verified_at' => now()]);

    $solicitud = SolicitudCredito::create([
        'user_id'          => $user->id,
        'nombre_completo'  => 'Carmen Xiu Balam',
        'curp'             => 'XIBC900615MYNXLR08',
        'modalidad_id'     => $mod->id,
        'monto_solicitado' => 28000,
        'tipo_garantia'    => $garantia,
        'estatus'          => 'Documentacion_Incompleta',
    ]);

    return [$user, $solicitud];
}

/** Claves que, según el wizard, son propias de cada modalidad. */
dataset('modalidades', [
    'Artesanal'     => ['Artesanal', ['constancia_artesano', 'cotizaciones_proveedor']],
    'Emprendedores' => ['Emprendedores', ['cotizaciones_proveedor', 'buro_credito', 'constancia_situacion']],
    'Sustentable'   => ['Sustentable', ['cotizaciones_proveedor', 'buro_credito', 'opinion_cumplimiento', 'plan_trabajo_sostenible']],
]);

const DOCUMENTOS_AVAL = ['id_aval', 'comprobante_domicilio_aval', 'acta_nacimiento_aval'];

test('los pendientes del expediente incluyen los de la modalidad y el aval', function (string $modalidad, array $propios) {
    [$user] = crearSolicitudCiudadano($modalidad);

    $this->actingAs($user)
        ->get('/mi-portal/expediente')
        ->assertOk()
        ->assertInertia(function ($page) use ($propios) {
            $page->component('portal/Expediente');

            foreach ([...$propios, ...DOCUMENTOS_AVAL] as $clave) {
                $page->has("tipos_documentos.$clave");
            }
        });
})->with('modalidades');

test('el expediente espera exactamente los mismos documentos que el wizard', function (string $modalidad) {
    [$user, $solicitud] = crearSolicitudCiudadano($modalidad);

    $esperados = DocumentoSolicitud::tiposRequeridos(
        $solicitud->modalidad_id,
        $solicitud->tipo_persona,
        $solicitud->tipo_garantia,
        null,
        null,
        (float) $solicitud->monto_solicitado,
    );

    $this->actingAs($user)
        ->get('/mi-portal/expediente')
        ->assertInertia(fn ($page) => $page->where('tipos_documentos', $esperados));
})->with(['Artesanal', 'Emprendedores', 'Sustentable']);

test('sin garantía guardada el expediente asume aval, igual que el wizard', function () {
    [$user] = crearSolicitudCiudadano('Sustentable', garantia: null);

    $this->actingAs($user)
        ->get('/mi-portal/expediente')
        ->assertInertia(function ($page) {
            foreach (DOCUMENTOS_AVAL as $clave) {
                $page->has("tipos_documentos.$clave");
            }
        });
});

test('los documentos pendientes se muestran con nombre legible, no con la clave interna', function () {
    [$user] = crearSolicitudCiudadano('Sustentable');

    $this->actingAs($user)
        ->get('/mi-portal/expediente')
        ->assertInertia(function ($page) {
            $page->where('tipos_documentos.opinion_cumplimiento', 'Opinión de Cumplimiento SAT')
                ->where('tipos_documentos.id_aval', 'Identificación del Aval');
        });
});

test('un documento ya subido de la modalidad muestra su nombre legible', function () {
    [$user, $solicitud] = crearSolicitudCiudadano('Sustentable');

    DocumentoSolicitud::create([
        'solicitud_id'    => $solicitud->id,
        'tipo_documento'  => 'opinion_cumplimiento',
        'nombre_original' => 'opinion.pdf',
        'ruta_archivo'    => 'solicitudes/1/opinion_cumplimiento.pdf',
        'estatus'         => 'Pendiente',
    ]);

    $this->actingAs($user)
        ->get('/mi-portal/expediente')
        ->assertInertia(fn ($page) => $page
            ->where('documentos.0.tipo_documento', 'opinion_cumplimiento')
            ->where('documentos.0.label', 'Opinión de Cumplimiento SAT'));
});
