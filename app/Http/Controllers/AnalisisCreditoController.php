<?php

namespace App\Http\Controllers;

use App\Models\AnalisisCredito;
use App\Models\AuditoriaLog;
use App\Models\SolicitudCredito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalisisCreditoController extends Controller
{
    public function create(SolicitudCredito $solicitud): Response
    {
        abort_if(!in_array($solicitud->estatus, ['En_Revision', 'Documentacion_Incompleta']), 403,
            'Solo se puede analizar solicitudes en revisión.');

        $solicitud->load(['user', 'modalidad', 'documentos', 'analisis']);

        return Inertia::render('Solicitudes/Analisis', [
            'solicitud' => [
                'id'               => $solicitud->id,
                'nombre_completo'  => $solicitud->nombre_completo,
                'curp'             => $solicitud->curp,
                'rfc'              => $solicitud->rfc,
                'municipio'        => $solicitud->municipio,
                'alta_sat'         => $solicitud->alta_sat,
                'monto_solicitado' => $solicitud->monto_solicitado,
                'modalidad'        => $solicitud->modalidad?->nombre,
                'giro_comercial'   => $solicitud->giro_comercial,
                'estatus'          => $solicitud->estatus,
            ],
            'analisis_existente' => $solicitud->analisis ? [
                'id'                      => $solicitud->analisis->id,
                'ingresos_mensuales'      => $solicitud->analisis->ingresos_mensuales,
                'gastos_mensuales'        => $solicitud->analisis->gastos_mensuales,
                'capacidad_pago'          => $solicitud->analisis->capacidad_pago,
                'relacion_deuda_ingreso'  => $solicitud->analisis->relacion_deuda_ingreso,
                'curp_valida'             => $solicitud->analisis->curp_valida,
                'rfc_activo'              => $solicitud->analisis->rfc_activo,
                'municipio_elegible'      => $solicitud->analisis->municipio_elegible,
                'giro_elegible'           => $solicitud->analisis->giro_elegible,
                'beneficiario_duplicado'  => $solicitud->analisis->beneficiario_duplicado,
                'alta_sat_verificada'     => $solicitud->analisis->alta_sat_verificada,
                'documentos_completos'    => $solicitud->analisis->documentos_completos,
                'referencias_verificadas' => $solicitud->analisis->referencias_verificadas,
                'antiguedad_negocio_meses'=> $solicitud->analisis->antiguedad_negocio_meses,
                'score_cualitativo'       => $solicitud->analisis->score_cualitativo,
                'observaciones_analisis'  => $solicitud->analisis->observaciones_analisis,
                'recomendacion'           => $solicitud->analisis->recomendacion,
                'monto_recomendado'       => $solicitud->analisis->monto_recomendado,
                'motivo_rechazo'          => $solicitud->analisis->motivo_rechazo,
            ] : null,
        ]);
    }

    public function store(Request $request, SolicitudCredito $solicitud): RedirectResponse
    {
        $data = $request->validate([
            'ingresos_mensuales'      => 'nullable|numeric|min:0',
            'gastos_mensuales'        => 'nullable|numeric|min:0',
            'curp_valida'             => 'boolean',
            'rfc_activo'              => 'boolean',
            'municipio_elegible'      => 'boolean',
            'giro_elegible'           => 'boolean',
            'beneficiario_duplicado'  => 'boolean',
            'alta_sat_verificada'     => 'boolean',
            'documentos_completos'    => 'boolean',
            'referencias_verificadas' => 'boolean',
            'antiguedad_negocio_meses'=> 'nullable|integer|min:0',
            'observaciones_analisis'  => 'nullable|string|max:2000',
            'recomendacion'           => 'required|in:Aprobar,Rechazar,Modificar_Monto',
            'monto_recomendado'       => 'nullable|numeric|min:100',
            'motivo_rechazo'          => 'required_if:recomendacion,Rechazar|nullable|string|max:500',
        ]);

        $ingresos = (float) ($data['ingresos_mensuales'] ?? 0);
        $gastos   = (float) ($data['gastos_mensuales'] ?? 0);
        $capacidad = $ingresos - $gastos;

        $cuotaEstimada = 0;
        if ($solicitud->monto_solicitado && $solicitud->modalidad) {
            $modalidad = $solicitud->modalidad;
            $tasa      = ((float) $modalidad->tasa_interes / 100) / 12;
            $plazo     = 12;
            $cuotaEstimada = $tasa > 0
                ? (float) $solicitud->monto_solicitado * ($tasa / (1 - pow(1 + $tasa, -$plazo)))
                : (float) $solicitud->monto_solicitado / $plazo;
        }

        $relacionDeuda = ($ingresos > 0) ? round(($cuotaEstimada / $ingresos) * 100, 2) : 0;

        $analisis = AnalisisCredito::updateOrCreate(
            ['solicitud_id' => $solicitud->id],
            array_merge($data, [
                'analista_id'            => auth()->id(),
                'fecha_analisis'         => now()->toDateString(),
                'capacidad_pago'         => $capacidad,
                'relacion_deuda_ingreso' => $relacionDeuda,
            ])
        );

        $analisis->update(['score_cualitativo' => $analisis->calcularScore()]);

        AuditoriaLog::registrar(
            'SolicitudCredito',
            $solicitud->id,
            'updated',
            'analisis',
            null,
            $data['recomendacion'],
            "Análisis crediticio registrado por " . auth()->user()?->name . ". Recomendación: {$data['recomendacion']}"
        );

        return back()->with('success', 'Análisis guardado. Recomendación: ' . $data['recomendacion']);
    }
}
