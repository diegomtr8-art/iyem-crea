<?php

namespace App\Http\Controllers;

use App\Models\AuditoriaLog;
use App\Models\Credito;
use App\Models\Reestructuracion;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\EstadoCredito;

class ReestructuracionController extends Controller
{
    public function create(Credito $credito): Response
    {
        $credito->load(['acreditado', 'modalidad', 'amortizaciones', 'reestructuraciones.autorizadoPor']);

        $fechaCorte = Carbon::now('America/Merida')->startOfDay();

        $calculo = $this->calcularBaseReestructuracion($credito, $fechaCorte);

        return Inertia::render('Creditos/Reestructuracion', [
            'credito' => [
                'id'             => $credito->id,
                'clave_contrato' => $credito->clave_contrato,
                'estatus'        => $credito->estatus,
                'monto_otorgado' => $credito->monto_otorgado,
                'tasa_ordinaria' => $credito->tasa_interes_ordinario,
                'acreditado'     => $credito->acreditado?->nombre_completo,
                'acreditado_id'  => $credito->acreditado_id,
                'saldo_pendiente'=> $calculo['base'],
                'mora_acumulada' => $calculo['mora'],
            ],
            'reestructuraciones_previas' => $credito->reestructuraciones->map(fn($r) => [
                'fecha'            => $r->fecha_reestructura->format('d/m/Y'),
                'motivo'           => $r->motivo,
                'saldo'            => $r->saldo_al_momento,
                'nuevo_plazo'      => $r->nuevo_plazo_meses,
                'autorizado_por'   => $r->autorizadoPor?->name,
                'numero_resolutivo'=> $r->numero_resolutivo,
            ]),
        ]);
    }

    public function store(Request $request, Credito $credito): RedirectResponse
    {
        $data = $request->validate([
            'fecha_reestructura'       => 'required|date',
            'motivo'                   => 'required|in:Dificultad_Economica,Desastre_Natural,Pandemia,Cambio_Actividad,Otro',
            'nuevo_plazo_meses'        => 'required|integer|min:1|max:60',
            'nueva_tasa_interes'       => 'required|numeric|min:0|max:100',
            'nueva_fecha_inicio_pagos' => 'required|date',
            'numero_resolutivo'        => 'nullable|string|max:100',
            'observaciones'            => 'nullable|string|max:2000',
        ]);

        $fechaCorte = Carbon::parse($data['fecha_reestructura'])->startOfDay();

        $calculo = $this->calcularBaseReestructuracion($credito, $fechaCorte);
        $baseReestructuracion = $calculo['base'];
        $moraCondonada = $calculo['mora'];

        return DB::transaction(function () use ($credito, $data, $baseReestructuracion, $moraCondonada) {
            $reestructuracion = Reestructuracion::create([
                'credito_id'               => $credito->id,
                'fecha_reestructura'       => $data['fecha_reestructura'],
                'motivo'                   => $data['motivo'],
                'saldo_al_momento'         => $baseReestructuracion,
                'mora_condonada'           => $moraCondonada,
                'nuevo_plazo_meses'        => $data['nuevo_plazo_meses'],
                'nueva_tasa_interes'       => $data['nueva_tasa_interes'],
                'nueva_fecha_inicio_pagos' => $data['nueva_fecha_inicio_pagos'],
                'autorizado_por'           => auth()->id(),
                'numero_resolutivo'        => $data['numero_resolutivo'] ?? null,
                'observaciones'            => $data['observaciones'] ?? null,
            ]);

            // Marcar cuotas pendientes como Reestructurada (preserva auditoría)
            $credito->amortizaciones()
                ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
                ->update(['estado' => 'Reestructurada', 'pago_restante' => 0]);

            $monto       = $baseReestructuracion;

            if ($monto <= 0) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto a reestructurar debe ser mayor a cero: el crédito no tiene capital o interés devengado pendiente.',
                ]);
            }

            $plazo       = (int) $data['nuevo_plazo_meses'];
            $tasa        = ((float) $data['nueva_tasa_interes'] / 100) / 12;
            $fechaInicio = Carbon::parse($data['nueva_fecha_inicio_pagos']);

            $cuotaFija = ($tasa > 0)
                ? $monto * ($tasa / (1 - pow(1 + $tasa, -$plazo)))
                : $monto / $plazo;

            // Determinar siguiente número de cuota
            $ultimaCuota = $credito->amortizaciones()->max('numero_cuota') ?? 0;

            $saldo = $monto;
            for ($i = 1; $i <= $plazo; $i++) {
                $interes = round($saldo * $tasa, 2);
                $capital = ($i === $plazo) ? round($saldo, 2) : round($cuotaFija - $interes, 2);
                $cuota   = round($capital + $interes, 2);
                $credito->amortizaciones()->create([
                    'numero_cuota'               => $ultimaCuota + $i,
                    'fecha_vencimiento'          => $fechaInicio->copy()->addMonths($i)->format('Y-m-d'),
                    'saldo_insoluto'             => round($saldo, 2),
                    'capital_esperado'           => $capital,
                    'interes_ordinario_esperado' => $interes,
                    'cuota_fija'                 => $cuota,
                    'pago_restante'              => $cuota,
                    'estado'                     => 'Pendiente',
                    'capital_pagado'             => 0,
                    'interes_ordinario_pagado'   => 0,
                    'interes_moratorio_pagado'   => 0,
                    'interes_moratorio_generado' => 0,
                ]);
                $saldo -= $capital;
            }

            $credito->update(['estatus' => EstadoCredito::ACTIVO]);

            AuditoriaLog::registrar(
                'Credito',
                $credito->id,
                'updated',
                'reestructuracion',
                $credito->estatus,
                'Activo',
                "Crédito reestructurado. Nuevo plazo: {$plazo} meses. Resolutivo: " .
                ($data['numero_resolutivo'] ?? 'N/A')
            );

            return redirect()->route('acreditados.show', $credito->acreditado_id)
                ->with('success', 'Reestructuración registrada y tabla de amortización regenerada.');
        });
    }

    private function calcularBaseReestructuracion(Credito $credito, Carbon $fechaCorte): array
    {
        $cuotas = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->get([
                'fecha_vencimiento',
                'capital_esperado',
                'capital_pagado',
                'interes_ordinario_esperado',
                'interes_ordinario_pagado',
                'interes_moratorio_generado',
                'interes_moratorio_pagado',
            ]);

        $capital = $cuotas->sum(fn ($c) => (float) $c->capital_esperado - (float) $c->capital_pagado);

        $devengado = $cuotas
            ->filter(fn ($c) => Carbon::parse($c->fecha_vencimiento)->startOfDay()->lte($fechaCorte))
            ->sum(fn ($c) => max(0, (float) $c->interes_ordinario_esperado - (float) $c->interes_ordinario_pagado));

        $mora = $cuotas->sum(
            fn ($c) => max(0, (float) $c->interes_moratorio_generado - (float) $c->interes_moratorio_pagado)
        );

        return [
            'base' => round($capital + $devengado, 2),
            'mora' => round($mora, 2),
        ];
    }
}
