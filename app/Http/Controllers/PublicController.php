<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

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
     * Procesa la consulta del acreditado mediante CURP y RFC.
     */
    public function consultar(Request $request)
    {
        // 1. Validamos la entrada del ciudadano
        $request->validate([
            'curp' => 'required|string|size:18',
            'rfc' => 'required|string'
        ], [
            'curp.required' => 'La CURP es obligatoria para la consulta.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'rfc.required' => 'El RFC es necesario para validar su identidad.'
        ]);

        // 2. Buscamos al acreditado por identidad
        $acreditado = Acreditado::where('curp', strtoupper($request->curp))
            ->where('rfc', strtoupper($request->rfc)) 
            ->first();

        // 3. Si no existe, regresamos con error
        if (!$acreditado) {
            return Redirect::back()->with('error', 'Los datos ingresados no coinciden con nuestros registros. Verifique su CURP y RFC.');
        }

        // 4. Obtenemos el crédito relacionado
        $credito = $acreditado->creditos()->first();

        /**
         * 5. Obtenemos la Tabla de Amortización (Proyección)
         * Se asume que en el modelo Credito existe la relación 'amortizaciones' 
         * o 'tablaAmortizacion'. Cámbialo según el nombre de tu método en el modelo.
         */
     
                   // En PublicController.php
                $tablaAmortizacion = $credito->amortizaciones()
                    ->orderBy('numero_cuota', 'asc')
                    ->get()
                    ->map(function($item) {
                        return [
                            'numero_cuota'      => $item->numero_cuota,
                            'fecha_vencimiento' => $item->fecha_vencimiento,
                            // Renombramos para el Frontend:
                            'capital'           => $item->capital_esperado ?? 0,
                            'ordinario'         => $item->interes_ordinario_esperado ?? 0,
                            'moratorio'         => $item->interes_moratorio_generado ?? 0,
                            'total_pagado'      => ($item->capital_pagado ?? 0) + ($item->interes_ordinario_pagado ?? 0),
                        ];
                    });

        // 6. Retornamos la vista con la data estructurada
        return Inertia::render('Welcome', [
            'acreditado' => [
                'id'              => $acreditado->id,
                'nombre'          => $acreditado->nombre_completo,
                'curp'            => $acreditado->curp,
                'rfc'             => $acreditado->rfc,
                'municipio'       => $acreditado->municipio,
                'domicilio'       => $acreditado->domicilio_fiscal,
                
                // Datos del crédito
                'monto_otorgado'  => $credito ? $credito->monto_otorgado : 0,
                'estatus'         => $credito ? $credito->estatus : 'No disponible',
                
                // TABLA DE AMORTIZACIÓN (Lo que quieres mostrar)
                'tabla'           => $tablaAmortizacion, 
                
                // PAGOS REALIZADOS (Vacío por ahora para evitar errores)
                'pagos'           => [], 
            ],
            'filters' => $request->only(['curp'])
        ])->with('success', 'Información recuperada correctamente.');
    }
}