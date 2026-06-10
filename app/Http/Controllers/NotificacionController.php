<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificacionController extends Controller
{
    public function cuotasManana()
    {
        $manana = Carbon::now('America/Merida')->addDay()->toDateString();
        $hoy    = Carbon::now('America/Merida')->toDateString();

        // Cuotas que vencen mañana (pendientes/parciales)
        $manana_list = DB::table('amortizaciones')
            ->join('creditos', 'creditos.id', '=', 'amortizaciones.credito_id')
            ->join('acreditados', 'acreditados.id', '=', 'creditos.acreditado_id')
            ->join('modalidad_creas', 'modalidad_creas.id', '=', 'creditos.modalidad_id')
            ->whereIn('amortizaciones.estado', ['Pendiente', 'Parcial'])
            ->where('amortizaciones.fecha_vencimiento', $manana)
            ->select(
                'acreditados.id as acreditado_id',
                'acreditados.nombre_completo',
                'creditos.id as credito_id',
                'creditos.clave_contrato',
                'modalidad_creas.nombre as modalidad',
                'amortizaciones.numero_cuota',
                'amortizaciones.pago_restante',
                DB::raw("'manana' as tipo")
            )
            ->get();

        // Cuotas vencidas hoy (para alertas urgentes)
        $hoy_list = DB::table('amortizaciones')
            ->join('creditos', 'creditos.id', '=', 'amortizaciones.credito_id')
            ->join('acreditados', 'acreditados.id', '=', 'creditos.acreditado_id')
            ->join('modalidad_creas', 'modalidad_creas.id', '=', 'creditos.modalidad_id')
            ->whereIn('amortizaciones.estado', ['Pendiente', 'Parcial'])
            ->where('amortizaciones.fecha_vencimiento', $hoy)
            ->select(
                'acreditados.id as acreditado_id',
                'acreditados.nombre_completo',
                'creditos.id as credito_id',
                'creditos.clave_contrato',
                'modalidad_creas.nombre as modalidad',
                'amortizaciones.numero_cuota',
                'amortizaciones.pago_restante',
                DB::raw("'hoy' as tipo")
            )
            ->get();

        return response()->json([
            'notificaciones' => $hoy_list->concat($manana_list)->values(),
            'total'          => $hoy_list->count() + $manana_list->count(),
        ]);
    }
}
