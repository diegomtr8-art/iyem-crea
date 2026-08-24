<?php

namespace App\Http\Controllers;

use App\Models\Acreditado;
use App\Models\Credito;
use App\Models\DocumentoSolicitud;
use App\Models\ModalidadCrea;
use App\Models\Interesado;
use App\Models\Amortizacion;
use App\Models\SolicitudCredito;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AcreditadoController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $estatus  = $request->get('estatus');
        $municipio = $request->get('municipio');

        $acreditados = Acreditado::with(['creditos.modalidad'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('municipio', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhereHas('creditos', fn($c) => $c->where('clave_contrato', 'like', "%{$search}%"));
                });
            })
            ->when($municipio, fn($q) => $q->where('municipio', $municipio))
            ->when($estatus, fn($q) => $q->whereHas('creditos', fn($c) => $c->where('estatus', $estatus)))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $kpis = [
            'total'     => Acreditado::count(),
            'activos'   => Credito::where('estatus', 'Activo')->count(),
            'morosos'   => Credito::where('estatus', 'Moroso')->count(),
            'liquidados'=> Credito::where('estatus', 'Liquidado')->count(),
            'cartera'   => (float) Credito::whereIn('estatus', ['Activo', 'Moroso'])->sum('monto_otorgado'),
        ];

        $municipios = Acreditado::whereNotNull('municipio')
            ->distinct()
            ->orderBy('municipio')
            ->pluck('municipio');

        return Inertia::render('Acreditados/Index', [
            'acreditados' => $acreditados,
            'filters'     => $request->only('search', 'estatus', 'municipio'),
            'kpis'        => $kpis,
            'municipios'  => $municipios,
        ]);
    }

    public function create(Request $request)
    {
        $modalidades = ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        $interesado = null;
        if ($request->has('interesado_id')) {
            $interesado = Interesado::find($request->interesado_id);
        }

        return Inertia::render('Acreditados/Create', [
            'modalidades'   => $modalidades,
            'interesado'    => $interesado,
            'prefill'       => $request->only(['nombre', 'municipio', 'modalidad', 'sexo', 'correo', 'interesado_id']),
            'regimenes_sat' => $this->regimenesFiscalesSat(),
        ]);
    }

    /**
     * Catálogo oficial del SAT (c_RegimenFiscal, CFDI 4.0) para personas físicas y morales.
     */
    private function regimenesFiscalesSat(): array
    {
        return [
            ['id' => '601', 'nombre' => 'General de Ley Personas Morales'],
            ['id' => '603', 'nombre' => 'Personas Morales con Fines no Lucrativos'],
            ['id' => '605', 'nombre' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios'],
            ['id' => '606', 'nombre' => 'Arrendamiento'],
            ['id' => '607', 'nombre' => 'Régimen de Enajenación o Adquisición de Bienes'],
            ['id' => '608', 'nombre' => 'Demás Ingresos'],
            ['id' => '609', 'nombre' => 'Consolidación'],
            ['id' => '610', 'nombre' => 'Residentes en el Extranjero sin Establecimiento Permanente en México'],
            ['id' => '611', 'nombre' => 'Ingresos por Dividendos (socios y accionistas)'],
            ['id' => '612', 'nombre' => 'Personas Físicas con Actividades Empresariales y Profesionales'],
            ['id' => '614', 'nombre' => 'Ingresos por Intereses'],
            ['id' => '615', 'nombre' => 'Régimen de los Ingresos por Obtención de Premios'],
            ['id' => '616', 'nombre' => 'Sin Obligaciones Fiscales'],
            ['id' => '620', 'nombre' => 'Sociedades Cooperativas de Producción que Optan por Diferir sus Ingresos'],
            ['id' => '621', 'nombre' => 'Incorporación Fiscal'],
            ['id' => '622', 'nombre' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'],
            ['id' => '623', 'nombre' => 'Opcional para Grupos de Sociedades'],
            ['id' => '624', 'nombre' => 'Coordinados'],
            ['id' => '625', 'nombre' => 'Actividades Empresariales con Ingresos a través de Plataformas Tecnológicas'],
            ['id' => '626', 'nombre' => 'Régimen Simplificado de Confianza (RESICO)'],
            ['id' => '628', 'nombre' => 'Hidrocarburos'],
            ['id' => '629', 'nombre' => 'De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales'],
            ['id' => '630', 'nombre' => 'Enajenación de Acciones en Bolsa de Valores'],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'interesado_id'    => 'nullable|exists:interesados,id',
            'nombre_completo'  => 'required|string|max:255',
            'municipio'        => 'required|string',
            'sexo'             => 'required|in:H,M',
            'rfc'              => 'nullable|string|max:13',
            'curp'             => 'nullable|string|max:18',
            'correo'           => 'nullable|email',
            'domicilio_fiscal' => 'nullable|string|max:500',
            'regimen_fiscal'   => 'nullable|string',
            'clave_pago'       => 'nullable|string',
            'modalidad_id'     => 'required|exists:modalidad_creas,id',
            'plazo_meses'      => 'required|integer|min:1|max:60',
            'fecha_entrega'    => 'required|date',
            'clave_contrato'   => 'required|string|unique:creditos,clave_contrato',
            'monto_otorgado'   => 'required|numeric|min:1',
        ]);

        $modalidad = ModalidadCrea::findOrFail($validated['modalidad_id']);

        $tasaOrdinaria = (float) $modalidad->tasa_interes;
        $tasaMoratoria = round($tasaOrdinaria * 2.5, 2);

        return DB::transaction(function () use ($validated, $modalidad, $tasaOrdinaria, $tasaMoratoria) {

            $acreditado = Acreditado::create([
                'nombre_completo'     => $validated['nombre_completo'],
                'rfc'                 => strtoupper($validated['rfc'] ?? ''),
                'curp'                => strtoupper($validated['curp'] ?? ''),
                'municipio'           => $validated['municipio'],
                'sexo'                => $validated['sexo'],
                'direccion_fiscal'    => $validated['domicilio_fiscal'],
                'correo'              => $validated['correo'],
                'regimen'             => $validated['regimen_fiscal'],
                'clave_personalizada' => $validated['clave_pago'],
            ]);

            $credito = $acreditado->creditos()->create([
                'modalidad_id'           => $modalidad->id,
                'clave_contrato'         => $validated['clave_contrato'],
                'monto_otorgado'         => $validated['monto_otorgado'],
                'plazo_meses'            => $validated['plazo_meses'],
                'fecha_entrega'          => $validated['fecha_entrega'],
                'tasa_interes_ordinario' => $tasaOrdinaria,
                'tasa_interes_moratorio' => $tasaMoratoria,
                'estatus'                => 'Activo',
            ]);

            $monto        = (float) $validated['monto_otorgado'];
            $plazo        = (int) $validated['plazo_meses'];
            $tasaMensual  = ($tasaOrdinaria / 100) / 12;
            $fechaInicial = Carbon::parse($validated['fecha_entrega']);
            $mesesProrroga = str_contains($modalidad->nombre, 'Sustentable') ? 3 : 0;

            $cuotaFija = ($tasaMensual > 0)
                ? $monto * ($tasaMensual / (1 - pow(1 + $tasaMensual, -$plazo)))
                : $monto / $plazo;

            $saldoInsoluto = $monto;

            for ($i = 1; $i <= $plazo; $i++) {
                $interesMes = round($saldoInsoluto * $tasaMensual, 2);
                $capitalMes = ($i === $plazo)
                    ? round($saldoInsoluto, 2)
                    : round($cuotaFija - $interesMes, 2);
                $cuotaTotal = round($capitalMes + $interesMes, 2);

                $fechaVencimiento = $fechaInicial->copy()->addMonths($i + $mesesProrroga);

                $credito->amortizaciones()->create([
                    'numero_cuota'               => $i,
                    'fecha_vencimiento'          => $fechaVencimiento->format('Y-m-d'),
                    'saldo_insoluto'             => round($saldoInsoluto, 2),
                    'capital_esperado'           => $capitalMes,
                    'interes_ordinario_esperado' => $interesMes,
                    'cuota_fija'                 => $cuotaTotal,
                    'pago_restante'              => $cuotaTotal,
                    'estado'                     => 'Pendiente',
                    'capital_pagado'             => 0,
                    'interes_ordinario_pagado'   => 0,
                    'interes_moratorio_pagado'   => 0,
                    'interes_moratorio_generado' => 0,
                ]);

                $saldoInsoluto -= $capitalMes;
            }

            if (!empty($validated['interesado_id'])) {
                Interesado::where('id', $validated['interesado_id'])
                    ->update(['estatus' => 'Convertido']);
            }

            return redirect()->route('acreditados.show', $acreditado->id)
                ->with('success', 'Expediente creado con éxito.');
        });
    }

    public function show(Acreditado $acreditado)
    {
        $acreditado->load(['creditos.modalidad', 'creditos.amortizaciones']);
        $credito = $acreditado->creditos->first();

        $amortizacionesParaVista = [];

        if ($credito) {
            $hoy       = Carbon::now('America/Merida')->startOfDay();
            $tasaDiaria = ($credito->tasa_interes_moratorio / 100) / 360;

            foreach ($credito->amortizaciones as $fila) {
                $row = $fila->toArray();

                if (in_array($fila->estado, ['Pagado', 'Condonado'])) {
                    $row['mora_al_dia']       = (float) ($fila->estado === 'Pagado' ? $fila->interes_moratorio_pagado : 0);
                    $row['dias_atraso']       = 0;
                    $row['en_periodo_gracia'] = false;
                    $row['total_a_pagar']     = 0;
                    $amortizacionesParaVista[] = $row;
                    continue;
                }

                $vencimiento = Carbon::parse($fila->fecha_vencimiento)->startOfDay();
                $moraActual  = 0.0;
                $diasAtraso  = 0;
                $enGracia    = false;

                if ($hoy->gt($vencimiento)) {
                    $diasAtraso = (int) $vencimiento->diffInDays($hoy);

                    if ($diasAtraso > 5) {
                        // Mora sobre saldo insoluto vencido (RO Cláusula 7a)
                        $saldoVencido = round(max(0, (float)$fila->saldo_insoluto - (float)$fila->capital_pagado), 2);
                        $moraActual   = round($saldoVencido * $tasaDiaria * $diasAtraso, 2);
                    } else {
                        $enGracia = true;
                    }
                }

                $row['mora_al_dia']      = $moraActual;
                $row['dias_atraso']      = $diasAtraso;
                $row['en_periodo_gracia'] = $enGracia;
                $row['total_a_pagar']    = round((float)$fila->pago_restante + $moraActual, 2);

                $amortizacionesParaVista[] = $row;
            }
        }

        return Inertia::render('Acreditados/Show', [
            'acreditado'     => $acreditado,
            'credito'        => $credito,
            'amortizaciones' => $amortizacionesParaVista,
        ]);
    }

    public function edit(Acreditado $acreditado)
    {
        $modalidades = ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);
        return Inertia::render('Acreditados/Edit', [
            'acreditado'  => $acreditado->load('creditos'),
            'modalidades' => $modalidades,
        ]);
    }

    public function update(Request $request, Acreditado $acreditado)
    {
        $validated = $request->validate([
            'nombre_completo'  => 'required|string|max:255',
            'municipio'        => 'required|string',
            'sexo'             => 'required|in:H,M',
            'rfc'              => 'nullable|string|max:13',
            'curp'             => 'nullable|string|max:18',
            'correo'           => 'nullable|email',
            'domicilio_fiscal' => 'nullable|string|max:500',
            'regimen_fiscal'   => 'nullable|string',
            'clave_pago'       => 'nullable|string',
        ]);

        $acreditado->update([
            'nombre_completo'     => $validated['nombre_completo'],
            'rfc'                 => strtoupper($validated['rfc'] ?? ''),
            'curp'                => strtoupper($validated['curp'] ?? ''),
            'municipio'           => $validated['municipio'],
            'sexo'                => $validated['sexo'],
            'direccion_fiscal'    => $validated['domicilio_fiscal'],
            'correo'              => $validated['correo'],
            'regimen'             => $validated['regimen_fiscal'],
            'clave_personalizada' => $validated['clave_pago'],
        ]);

        return redirect()->route('acreditados.show', $acreditado->id)
            ->with('success', 'Expediente actualizado.');
    }

    public function destroy(Acreditado $acreditado)
    {
        $acreditado->delete();
        return redirect()->route('acreditados.index')
            ->with('success', 'Acreditado eliminado.');
    }

    public function expediente(Acreditado $acreditado): \Inertia\Response
    {
        $acreditado->load([
            'creditos.modalidad',
            'creditos.amortizaciones',
            'creditos.pagos' => fn($q) => $q->where('cancelado', false)->orderByDesc('fecha_pago'),
            'creditos.gestionesCobranza',
            'creditos.expedienteJuridico',
        ]);

        $credito = $acreditado->creditos->first();

        $solicitud = SolicitudCredito::where('acreditado_id', $acreditado->id)
            ->with(['documentos', 'modalidad', 'user'])
            ->first();

        $stats = null;
        if ($credito) {
            $amorts          = $credito->amortizaciones;
            $cuotasPagadas   = $amorts->where('estado', 'Pagado')->count();
            $cuotasActivas   = $amorts->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia']);
            $totalPagado     = $credito->pagos->sum('monto_recibido');
            $capitalPendiente= $cuotasActivas->sum('pago_restante');
            $proximaCuota    = $cuotasActivas->sortBy('fecha_vencimiento')->first();
            $hoy             = Carbon::today();

            $primeraVencida  = $cuotasActivas
                ->filter(fn($a) => Carbon::parse($a->fecha_vencimiento)->lt($hoy))
                ->sortBy('fecha_vencimiento')
                ->first();

            $stats = [
                'cuotas_pagadas'   => $cuotasPagadas,
                'cuotas_pendientes'=> $cuotasActivas->count(),
                'total_cuotas'     => $amorts->whereNotIn('estado', ['Condonado'])->count(),
                'total_pagado'     => $totalPagado,
                'capital_pendiente'=> $capitalPendiente,
                'dias_atraso'      => $primeraVencida
                    ? Carbon::parse($primeraVencida->fecha_vencimiento)->diffInDays($hoy)
                    : 0,
                'proxima_cuota' => $proximaCuota ? [
                    'numero' => $proximaCuota->numero_cuota,
                    'fecha'  => Carbon::parse($proximaCuota->fecha_vencimiento)->format('d/m/Y'),
                    'monto'  => (float) $proximaCuota->pago_restante,
                ] : null,
            ];
        }

        return Inertia::render('Acreditados/Expediente', [
            'acreditado' => [
                'id'                  => $acreditado->id,
                'nombre_completo'     => $acreditado->nombre_completo,
                'curp'                => $acreditado->curp,
                'rfc'                 => $acreditado->rfc,
                'sexo'                => $acreditado->sexo,
                'municipio'           => $acreditado->municipio,
                'direccion_fiscal'    => $acreditado->direccion_fiscal,
                'correo'              => $acreditado->correo,
                'regimen'             => $acreditado->regimen,
                'clave_personalizada' => $acreditado->clave_personalizada,
                'created_at'          => $acreditado->created_at->format('d/m/Y'),
            ],
            'credito' => $credito ? [
                'id'                     => $credito->id,
                'clave_contrato'         => $credito->clave_contrato,
                'monto_otorgado'         => (float) $credito->monto_otorgado,
                'plazo_meses'            => $credito->plazo_meses,
                'fecha_entrega'          => $credito->fecha_entrega?->format('d/m/Y'),
                'tasa_interes_ordinario' => (float) $credito->tasa_interes_ordinario,
                'tasa_interes_moratorio' => (float) $credito->tasa_interes_moratorio,
                'estatus'                => $credito->estatus,
                'modalidad'              => $credito->modalidad?->nombre,
                'tiene_juridico'         => $credito->expedienteJuridico !== null,
            ] : null,
            'solicitud' => $solicitud ? [
                'id'                  => $solicitud->id,
                'estatus'             => $solicitud->estatus,
                'fecha_nacimiento'    => $solicitud->fecha_nacimiento?->format('d/m/Y'),
                'telefono'            => $solicitud->telefono,
                'giro_comercial'      => $solicitud->giro_comercial,
                'destino_credito'     => $solicitud->destino_credito,
                'descripcion_negocio' => $solicitud->descripcion_negocio,
                'monto_solicitado'    => (float) $solicitud->monto_solicitado,
                'alta_sat'            => $solicitud->alta_sat,
                'mayahablante'        => $solicitud->mayahablante,
                'discapacidad'        => $solicitud->discapacidad,
                'user_email'          => $solicitud->user?->email,
            ] : null,
            'documentos' => $solicitud
                ? $solicitud->documentos->map(fn($d) => [
                    'id'              => $d->id,
                    'tipo_documento'  => $d->tipo_documento,
                    'label'           => DocumentoSolicitud::tiposRequeridos()[$d->tipo_documento] ?? $d->tipo_documento,
                    'nombre_original' => $d->nombre_original,
                    'estatus'         => $d->estatus,
                    'observacion'     => $d->observacion,
                    'url'             => $d->url,
                    'updated_at'      => $d->updated_at->format('d/m/Y'),
                ])->values()
                : collect(),
            'pagos_recientes' => $credito
                ? $credito->pagos->take(5)->map(fn($p) => [
                    'folio'          => $p->folio,
                    'fecha_pago'     => $p->fecha_pago?->format('d/m/Y'),
                    'monto_recibido' => (float) $p->monto_recibido,
                    'forma_pago'     => $p->forma_pago,
                ])->values()
                : collect(),
            'stats' => $stats,
        ]);
    }
}
