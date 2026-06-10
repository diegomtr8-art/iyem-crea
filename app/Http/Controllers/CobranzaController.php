<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\ExpedienteJuridico;
use App\Models\GestionCobranza;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CobranzaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Credito::where('estatus', 'Moroso')
            ->with(['acreditado', 'modalidad', 'amortizaciones', 'pagos', 'gestionesCobranza', 'expedienteJuridico'])
            ->orderByDesc('updated_at');

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->whereHas('acreditado', fn($q) =>
                $q->where('nombre_completo', 'like', "%{$b}%")
                  ->orWhere('curp', 'like', "%{$b}%")
            )->orWhere('clave_contrato', 'like', "%{$b}%");
        }

        if ($request->filled('rango')) {
            $query->get(); // se filtra en PHP después del cálculo de días
        }

        $todos = $query->get()->map(fn($c) => $this->formatearCredito($c));

        if ($request->filled('rango')) {
            $rango = $request->rango;
            $todos = $todos->filter(function ($c) use ($rango) {
                $d = $c['dias_atraso'];
                return match ($rango) {
                    '0-30'  => $d > 0 && $d <= 30,
                    '31-60' => $d > 30 && $d <= 60,
                    '61-90' => $d > 60 && $d <= 90,
                    '+90'   => $d > 90,
                    default => true,
                };
            })->values();
        }

        return Inertia::render('Cobranza/Index', [
            'creditos' => $todos,
            'kpis'     => $this->calcularKpis(),
            'filters'  => $request->only(['buscar', 'rango']),
        ]);
    }

    public function show(Credito $credito): Response
    {
        $credito->load([
            'acreditado', 'modalidad', 'amortizaciones',
            'pagos', 'gestionesCobranza.usuario', 'expedienteJuridico',
        ]);

        $hoy        = Carbon::today();
        $pendientes = $credito->amortizaciones->where('estado', 'Pendiente')->sortBy('fecha_vencimiento');
        $primera    = $pendientes->filter(fn($a) => Carbon::parse($a->fecha_vencimiento)->lt($hoy))->first();
        $diasAtraso = $primera ? Carbon::parse($primera->fecha_vencimiento)->diffInDays($hoy) : 0;

        return Inertia::render('Cobranza/Show', [
            'credito' => [
                'id'                    => $credito->id,
                'clave_contrato'        => $credito->clave_contrato,
                'monto_otorgado'        => $credito->monto_otorgado,
                'plazo_meses'           => $credito->plazo_meses,
                'fecha_entrega'         => $credito->fecha_entrega->format('d/m/Y'),
                'tasa_moratorio'        => $credito->tasa_interes_moratorio,
                'deuda_total'           => $pendientes->sum('pago_restante'),
                'cuotas_pendientes'     => $pendientes->count(),
                'dias_atraso'           => $diasAtraso,
                'ultimo_pago'           => $credito->pagos->where('cancelado', false)->sortByDesc('fecha_pago')->first()?->fecha_pago?->format('d/m/Y'),
                'modalidad'             => $credito->modalidad?->nombre,
                'acreditado' => [
                    'id'              => $credito->acreditado->id,
                    'nombre_completo' => $credito->acreditado->nombre_completo,
                    'curp'            => $credito->acreditado->curp,
                    'rfc'             => $credito->acreditado->rfc,
                    'municipio'       => $credito->acreditado->municipio,
                    'correo'          => $credito->acreditado->correo,
                ],
                'expediente_juridico' => $credito->expedienteJuridico ? [
                    'id'      => $credito->expedienteJuridico->id,
                    'estatus' => $credito->expedienteJuridico->estatus,
                ] : null,
            ],
            'gestiones' => $credito->gestionesCobranza->map(fn($g) => [
                'id'                    => $g->id,
                'tipo_gestion'          => $g->tipo_gestion,
                'descripcion'           => $g->descripcion,
                'resultado'             => $g->resultado,
                'fecha_gestion'         => $g->fecha_gestion->format('d/m/Y'),
                'fecha_compromiso_pago' => $g->fecha_compromiso_pago?->format('d/m/Y'),
                'monto_comprometido'    => $g->monto_comprometido,
                'usuario'               => $g->usuario->name,
            ])->values(),
            'cuotas_proximas' => $pendientes->take(6)->map(fn($a) => [
                'numero_cuota'     => $a->numero_cuota,
                'fecha_vencimiento'=> Carbon::parse($a->fecha_vencimiento)->format('d/m/Y'),
                'pago_restante'    => $a->pago_restante,
                'cuota_fija'       => $a->cuota_fija,
            ])->values(),
        ]);
    }

    public function storeGestion(Request $request, Credito $credito): RedirectResponse
    {
        $data = $request->validate([
            'tipo_gestion'          => 'required|in:Llamada,Visita,WhatsApp,Email,Carta,Acuerdo,Otro',
            'descripcion'           => 'required|string|max:2000',
            'resultado'             => 'required|in:Contactado,No_Contactado,Promesa_Pago,Sin_Respuesta,Acuerdo_Alcanzado,Otro',
            'fecha_gestion'         => 'required|date',
            'fecha_compromiso_pago' => 'nullable|date',
            'monto_comprometido'    => 'nullable|numeric|min:0',
        ]);

        $data['credito_id']    = $credito->id;
        $data['acreditado_id'] = $credito->acreditado_id;
        $data['usuario_id']    = auth()->id();

        GestionCobranza::create($data);

        return back()->with('success', 'Gestión de cobranza registrada correctamente.');
    }

    public function enviarJuridico(Request $request, Credito $credito): RedirectResponse
    {
        if ($credito->expedienteJuridico) {
            return back()->with('error', 'Este crédito ya tiene un expediente jurídico.');
        }

        $data = $request->validate([
            'fecha_envio_juridico'      => 'required|date',
            'monto_reclamado'           => 'nullable|numeric|min:0',
            'tribunal'                  => 'nullable|string|max:255',
            'juzgado'                   => 'nullable|string|max:255',
            'numero_expediente_judicial'=> 'nullable|string|max:100',
            'observaciones'             => 'nullable|string|max:3000',
        ]);

        $data['credito_id']    = $credito->id;
        $data['acreditado_id'] = $credito->acreditado_id;
        $data['usuario_id']    = auth()->id();

        ExpedienteJuridico::create($data);

        return redirect()->route('juridico.index')
            ->with('success', "Crédito {$credito->clave_contrato} enviado a Cobranza Jurídica.");
    }

    private function formatearCredito(Credito $c): array
    {
        $hoy        = Carbon::today();
        $pendientes = $c->amortizaciones->where('estado', 'Pendiente')->sortBy('fecha_vencimiento');
        $primera    = $pendientes->filter(fn($a) => Carbon::parse($a->fecha_vencimiento)->lt($hoy))->first();
        $diasAtraso = $primera ? Carbon::parse($primera->fecha_vencimiento)->diffInDays($hoy) : 0;

        return [
            'id'               => $c->id,
            'clave_contrato'   => $c->clave_contrato,
            'monto_otorgado'   => $c->monto_otorgado,
            'deuda_total'      => $pendientes->sum('pago_restante'),
            'cuotas_pendientes'=> $pendientes->count(),
            'dias_atraso'      => $diasAtraso,
            'ultimo_pago'      => $c->pagos->where('cancelado', false)->sortByDesc('fecha_pago')->first()?->fecha_pago?->format('d/m/Y'),
            'gestiones_count'  => $c->gestionesCobranza->count(),
            'tiene_juridico'   => $c->expedienteJuridico !== null,
            'estatus_juridico' => $c->expedienteJuridico?->estatus,
            'modalidad'        => $c->modalidad?->nombre,
            'acreditado' => [
                'nombre_completo' => $c->acreditado->nombre_completo,
                'municipio'       => $c->acreditado->municipio,
                'correo'          => $c->acreditado->correo,
            ],
        ];
    }

    private function calcularKpis(): array
    {
        $morosos = Credito::where('estatus', 'Moroso')
            ->with(['amortizaciones', 'pagos'])
            ->get();

        $hoy  = Carbon::today();
        $data = $morosos->map(function ($c) use ($hoy) {
            $pendientes = $c->amortizaciones->where('estado', 'Pendiente')->sortBy('fecha_vencimiento');
            $primera    = $pendientes->filter(fn($a) => Carbon::parse($a->fecha_vencimiento)->lt($hoy))->first();
            $dias       = $primera ? Carbon::parse($primera->fecha_vencimiento)->diffInDays($hoy) : 0;
            return ['dias' => $dias, 'deuda' => $pendientes->sum('pago_restante')];
        });

        return [
            'total_morosos'    => $morosos->count(),
            'monto_total_mora' => $data->sum('deuda'),
            'rango_0_30'       => $data->filter(fn($m) => $m['dias'] > 0 && $m['dias'] <= 30)->count(),
            'rango_31_60'      => $data->filter(fn($m) => $m['dias'] > 30 && $m['dias'] <= 60)->count(),
            'rango_61_90'      => $data->filter(fn($m) => $m['dias'] > 60 && $m['dias'] <= 90)->count(),
            'rango_mas_90'     => $data->filter(fn($m) => $m['dias'] > 90)->count(),
            'en_juridico'      => ExpedienteJuridico::count(),
            'gestiones_hoy'    => GestionCobranza::whereDate('fecha_gestion', $hoy)->count(),
        ];
    }
}
