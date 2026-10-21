<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PublicController extends Controller
{
    /**
     * Muestra la página de bienvenida (Portal Ciudadano).
     */
    public function index()
    {
        return Inertia::render('Welcome', [
            'acreditado' => null,
        ]);
    }

    /**
     * Aviso de Privacidad del programa CREA (acceso público).
     */
    public function avisoPrivacidad()
    {
        return Inertia::render('Legal/AvisoPrivacidad');
    }

    /**
     * Procesa la consulta del acreditado mediante CURP y número de contrato.
     */
    public function consultar(Request $request)
    {
        // 1. Validamos la entrada del ciudadano
        $request->validate([
            'curp' => 'required|string|size:18',
            'clave_contrato' => 'required|string|max:50',
        ], [
            'curp.required' => 'La CURP es obligatoria para la consulta.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'clave_contrato.required' => 'El número de contrato es obligatorio para la consulta.',
            'clave_contrato.max' => 'El número de contrato es demasiado largo.',
        ]);

        // 2. Buscamos el crédito por número de contrato y CURP del acreditado
        $credito = Credito::where('clave_contrato', trim($request->clave_contrato))
            ->whereHas('acreditado', fn ($q) => $q->where('curp', strtoupper(trim($request->curp))))
            ->first();

        // 3. Mensaje único: no revela si la persona existe ni si tiene crédito
        if (! $credito) {
            return Redirect::back()->with('error', 'No encontramos un crédito con esos datos. Verifica tu CURP y tu número de contrato.');
        }

        /**
         * 5. Obtenemos la Tabla de Amortización (Proyección)
         * Se asume que en el modelo Credito existe la relación 'amortizaciones'
         * o 'tablaAmortizacion'. Cámbialo según el nombre de tu método en el modelo.
         */

        // En PublicController.php
        $tablaAmortizacion = $credito->amortizaciones()
            ->orderBy('numero_cuota', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'numero_cuota' => $item->numero_cuota,
                    'fecha_vencimiento' => $item->fecha_vencimiento,
                    // Renombramos para el Frontend:
                    'capital' => $item->capital_esperado ?? 0,
                    'ordinario' => $item->interes_ordinario_esperado ?? 0,
                    'moratorio' => $item->interes_moratorio_generado ?? 0,
                    'total_pagado' => ($item->capital_pagado ?? 0) + ($item->interes_ordinario_pagado ?? 0),
                ];
            });

        // 6. Cuotas pendientes (misma regla de estados que "Mi crédito" del portal)
        $pendientes = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->orderBy('fecha_vencimiento')
            ->get();

        $proxima = $pendientes->first();
        $saldoPendiente = round((float) $pendientes->sum('pago_restante'), 2);

        // 7. Solo se devuelve lo necesario: sin domicilio ni datos de contacto
        return Inertia::render('Welcome', [
            'acreditado' => [
                'clave_contrato' => $credito->clave_contrato,
                'estatus' => $credito->estatus,
                'saldo_pendiente' => $saldoPendiente,
                'proxima_cuota' => $proxima ? [
                    'fecha_vencimiento' => $proxima->fecha_vencimiento?->format('d/m/Y'),
                    'monto' => round((float) $proxima->pago_restante, 2),
                ] : null,
                'tabla' => $tablaAmortizacion,
            ],
            'filters' => $request->only(['curp']),
        ])->with('success', 'Información recuperada correctamente.');
    }
}
