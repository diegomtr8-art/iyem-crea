<?php

namespace App\Http\Controllers;

use App\Models\AnuncioCiudadano;
use App\Models\Credito;
use App\Models\ExpedienteJuridico;
use App\Models\SolicitudCredito;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CobranzaJuridicaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ExpedienteJuridico::with([
            'credito.acreditado',
            'credito.modalidad',
            'credito.amortizaciones',
            'usuario',
        ])->orderByDesc('updated_at');

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('numero_expediente_judicial', 'like', "%{$b}%")
                  ->orWhere('tribunal', 'like', "%{$b}%")
                  ->orWhereHas('credito.acreditado', fn($q2) =>
                      $q2->where('nombre_completo', 'like', "%{$b}%")
                         ->orWhere('curp', 'like', "%{$b}%")
                  )
                  ->orWhereHas('credito', fn($q2) =>
                      $q2->where('clave_contrato', 'like', "%{$b}%")
                  );
            });
        }

        $expedientes = $query->get()->map(fn($e) => $this->formatearExpediente($e));

        return Inertia::render('Juridico/Index', [
            'expedientes' => $expedientes,
            'kpis'        => $this->calcularKpis(),
            'filters'     => $request->only(['estatus', 'buscar']),
        ]);
    }

    public function show(ExpedienteJuridico $juridico): Response
    {
        $juridico->load([
            'credito.acreditado',
            'credito.modalidad',
            'credito.amortizaciones',
            'credito.pagos',
            'credito.gestionesCobranza.usuario',
            'usuario',
        ]);

        $pendientes = $juridico->credito->amortizaciones->where('estado', 'Pendiente');
        $hoy        = Carbon::today();

        return Inertia::render('Juridico/Show', [
            'expediente' => [
                'id'                        => $juridico->id,
                'estatus'                   => $juridico->estatus,
                'fecha_envio_juridico'      => $juridico->fecha_envio_juridico->format('d/m/Y'),
                'tribunal'                  => $juridico->tribunal,
                'juzgado'                   => $juridico->juzgado,
                'numero_expediente_judicial'=> $juridico->numero_expediente_judicial,
                'monto_reclamado'           => $juridico->monto_reclamado ?? $pendientes->sum('pago_restante'),
                'observaciones'             => $juridico->observaciones,
                'dias_en_juridico'          => Carbon::parse($juridico->fecha_envio_juridico)->diffInDays($hoy),
                'usuario'                   => $juridico->usuario->name,
                'credito' => [
                    'id'             => $juridico->credito->id,
                    'clave_contrato' => $juridico->credito->clave_contrato,
                    'monto_otorgado' => $juridico->credito->monto_otorgado,
                    'fecha_entrega'  => $juridico->credito->fecha_entrega->format('d/m/Y'),
                    'modalidad'      => $juridico->credito->modalidad?->nombre,
                ],
                'acreditado' => [
                    'id'              => $juridico->credito->acreditado->id,
                    'nombre_completo' => $juridico->credito->acreditado->nombre_completo,
                    'curp'            => $juridico->credito->acreditado->curp,
                    'rfc'             => $juridico->credito->acreditado->rfc,
                    'municipio'       => $juridico->credito->acreditado->municipio,
                    'correo'          => $juridico->credito->acreditado->correo,
                ],
                'deuda_actual'      => $pendientes->sum('pago_restante'),
                'cuotas_pendientes' => $pendientes->count(),
                'ultimo_pago'       => $juridico->credito->pagos->where('cancelado', false)->sortByDesc('fecha_pago')->first()?->fecha_pago?->format('d/m/Y'),
            ],
            'historial_gestiones' => $juridico->credito->gestionesCobranza->map(fn($g) => [
                'tipo_gestion'  => $g->tipo_gestion,
                'descripcion'   => $g->descripcion,
                'resultado'     => $g->resultado,
                'fecha_gestion' => $g->fecha_gestion->format('d/m/Y'),
                'usuario'       => $g->usuario->name,
            ])->take(5)->values(),
        ]);
    }

    public function update(Request $request, ExpedienteJuridico $juridico): RedirectResponse
    {
        $data = $request->validate([
            'estatus'                    => 'required|in:Pendiente,En_Demanda,En_Platicas,Reestructurada,Recuperada',
            'tribunal'                   => 'nullable|string|max:255',
            'juzgado'                    => 'nullable|string|max:255',
            'numero_expediente_judicial' => 'nullable|string|max:100',
            'monto_reclamado'            => 'nullable|numeric|min:0',
            'observaciones'              => 'nullable|string|max:3000',
            // Campos exclusivos de reestructuración
            'nuevo_monto'                => 'required_if:estatus,Reestructurada|nullable|numeric|min:1',
            'nuevo_plazo_meses'          => 'required_if:estatus,Reestructurada|nullable|integer|min:1|max:60',
            'nueva_fecha_inicio'         => 'required_if:estatus,Reestructurada|nullable|date',
        ]);

        DB::transaction(function () use ($data, $juridico) {
            $juridico->update([
                'estatus'                    => $data['estatus'],
                'tribunal'                   => $data['tribunal'] ?? $juridico->tribunal,
                'juzgado'                    => $data['juzgado'] ?? $juridico->juzgado,
                'numero_expediente_judicial' => $data['numero_expediente_judicial'] ?? $juridico->numero_expediente_judicial,
                'monto_reclamado'            => $data['monto_reclamado'] ?? $juridico->monto_reclamado,
                'observaciones'              => $data['observaciones'] ?? $juridico->observaciones,
            ]);

            if ($data['estatus'] === 'Recuperada') {
                $juridico->credito->update(['estatus' => 'Activo']);
            }

            if ($data['estatus'] === 'Reestructurada') {
                $this->reestructurarCredito(
                    $juridico->credito,
                    (float)  $data['nuevo_monto'],
                    (int)    $data['nuevo_plazo_meses'],
                    Carbon::parse($data['nueva_fecha_inicio']),
                );
            }
        });

        return back()->with('success', 'Expediente jurídico actualizado correctamente.');
    }

    private function reestructurarCredito(
        Credito $credito,
        float   $nuevoMonto,
        int     $nuevoPlazo,
        Carbon  $nuevaFechaInicio,
    ): void {
        $credito->load('modalidad');
        $modalidad     = $credito->modalidad;
        $tasaOrdinaria = (float) $modalidad->tasa_interes;
        $tasaMoratoria = round($tasaOrdinaria * 2.5, 2);
        $tasaMensual   = ($tasaOrdinaria / 100) / 12;
        $mesesProrroga = str_contains($modalidad->nombre, 'Sustentable') ? 3 : 0;

        // Condonar amortizaciones pendientes/parciales restantes
        $credito->amortizaciones()
            ->whereIn('estado', ['Pendiente', 'Parcial'])
            ->update([
                'estado'        => 'Condonado',
                'pago_restante' => 0,
            ]);

        // Determinar el siguiente número de cuota
        $ultimaCuota = $credito->amortizaciones()->max('numero_cuota') ?? 0;

        // Calcular cuota fija con nueva tabla
        $cuotaFija = ($tasaMensual > 0)
            ? $nuevoMonto * ($tasaMensual / (1 - pow(1 + $tasaMensual, -$nuevoPlazo)))
            : $nuevoMonto / $nuevoPlazo;

        $saldo = $nuevoMonto;
        for ($i = 1; $i <= $nuevoPlazo; $i++) {
            $interes = round($saldo * $tasaMensual, 2);
            $capital = ($i === $nuevoPlazo) ? round($saldo, 2) : round($cuotaFija - $interes, 2);
            $cuota   = round($capital + $interes, 2);

            $credito->amortizaciones()->create([
                'numero_cuota'               => $ultimaCuota + $i,
                'fecha_vencimiento'          => $nuevaFechaInicio->copy()->addMonths($i + $mesesProrroga)->format('Y-m-d'),
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

        // Reactivar el crédito con las nuevas tasas
        $credito->update([
            'tasa_interes_ordinario' => $tasaOrdinaria,
            'tasa_interes_moratorio' => $tasaMoratoria,
            'estatus'                => 'Activo',
        ]);

        // Notificar al ciudadano si tiene portal vinculado
        $solicitud = SolicitudCredito::where('credito_id', $credito->id)->first();
        if ($solicitud?->user_id) {
            AnuncioCiudadano::create([
                'user_id'    => $solicitud->user_id,
                'titulo'     => 'Tu crédito fue reestructurado',
                'mensaje'    => "Tu crédito CREA ({$credito->clave_contrato}) ha sido reestructurado. "
                              . "Nuevo plazo: {$nuevoPlazo} meses por $" . number_format($nuevoMonto, 2)
                              . ". Ya puedes consultar tu nueva tabla de amortización.",
                'tipo'       => 'info',
                'url_accion' => route('portal.credito'),
            ]);
        }
    }

    private function formatearExpediente(ExpedienteJuridico $e): array
    {
        $pendientes = $e->credito->amortizaciones->where('estado', 'Pendiente');
        return [
            'id'                        => $e->id,
            'estatus'                   => $e->estatus,
            'fecha_envio_juridico'      => $e->fecha_envio_juridico->format('d/m/Y'),
            'tribunal'                  => $e->tribunal,
            'juzgado'                   => $e->juzgado,
            'numero_expediente_judicial'=> $e->numero_expediente_judicial,
            'monto_reclamado'           => $e->monto_reclamado ?? $pendientes->sum('pago_restante'),
            'dias_en_juridico'          => Carbon::parse($e->fecha_envio_juridico)->diffInDays(Carbon::today()),
            'credito' => [
                'id'             => $e->credito->id,
                'clave_contrato' => $e->credito->clave_contrato,
                'modalidad'      => $e->credito->modalidad?->nombre,
            ],
            'acreditado' => [
                'nombre_completo' => $e->credito->acreditado->nombre_completo,
                'municipio'       => $e->credito->acreditado->municipio,
            ],
            'usuario' => $e->usuario->name,
        ];
    }

    private function calcularKpis(): array
    {
        $expedientes = ExpedienteJuridico::with(['credito.amortizaciones'])->get();
        return [
            'total'         => $expedientes->count(),
            'pendiente'     => $expedientes->where('estatus', 'Pendiente')->count(),
            'en_demanda'    => $expedientes->where('estatus', 'En_Demanda')->count(),
            'en_platicas'   => $expedientes->where('estatus', 'En_Platicas')->count(),
            'reestructurada'=> $expedientes->where('estatus', 'Reestructurada')->count(),
            'recuperada'    => $expedientes->where('estatus', 'Recuperada')->count(),
            'monto_total'   => $expedientes->sum(fn($e) =>
                $e->monto_reclamado ?? $e->credito->amortizaciones->where('estado', 'Pendiente')->sum('pago_restante')
            ),
        ];
    }
}
