<?php

namespace App\Http\Controllers;

use App\Models\AnuncioCiudadano;
use App\Models\AuditoriaLog;
use App\Models\ComprobacionUso;
use App\Models\Credito;
use App\Models\Desembolso;
use App\Models\PresupuestoPrograma;
use App\Models\SolicitudCredito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DesembolsoController extends Controller
{
    public function create(Credito $credito): Response
    {
        abort_if($credito->desembolso, 422, 'Este crédito ya tiene un desembolso registrado.');

        $credito->load(['acreditado', 'modalidad']);

        return Inertia::render('Creditos/Desembolso', [
            'credito' => [
                'id'              => $credito->id,
                'clave_contrato'  => $credito->clave_contrato,
                'monto_otorgado'  => $credito->monto_otorgado,
                'fecha_entrega'   => $credito->fecha_entrega?->format('Y-m-d'),
                'estatus'         => $credito->estatus,
                'acreditado_nombre' => $credito->acreditado?->nombre_completo,
                'acreditado_id'   => $credito->acreditado_id,
                'modalidad'       => $credito->modalidad?->nombre,
            ],
        ]);
    }

    public function store(Request $request, Credito $credito): RedirectResponse
    {
        abort_if($credito->desembolso, 422, 'Este crédito ya tiene un desembolso registrado.');

        $this->verificarPresupuestoDisponible($credito);

        $data = $request->validate([
            'fecha_desembolso'   => 'required|date',
            'monto_desembolsado' => ['required', 'numeric', 'min:0.01', 'max:' . (float)$credito->monto_otorgado],
            'forma_desembolso'   => 'required|in:Transferencia,Cheque,Efectivo,Deposito',
            'banco_destino'      => 'nullable|string|max:100',
            'cuenta_destino'     => 'nullable|string|max:50',
            'folio_cheque'       => 'nullable|string|max:50',
            'referencia'         => 'nullable|string|max:100',
            'observaciones'      => 'nullable|string|max:1000',
            'comprobante'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $ruta = null;
        if ($request->hasFile('comprobante')) {
            $ruta = $request->file('comprobante')->store('desembolsos', 'public');
        }

        $desembolso = Desembolso::create([
            'credito_id'          => $credito->id,
            'fecha_desembolso'    => $data['fecha_desembolso'],
            'monto_desembolsado'  => $data['monto_desembolsado'],
            'forma_desembolso'    => $data['forma_desembolso'],
            'banco_destino'       => $data['banco_destino'] ?? null,
            'cuenta_destino'      => $data['cuenta_destino'] ?? null,
            'folio_cheque'        => $data['folio_cheque'] ?? null,
            'referencia'          => $data['referencia'] ?? null,
            'registrado_por'      => auth()->id(),
            'comprobante_ruta'    => $ruta,
            'observaciones'       => $data['observaciones'] ?? null,
        ]);

        AuditoriaLog::registrar(
            'Desembolso',
            $desembolso->id,
            'created',
            null,
            null,
            null,
            "Desembolso registrado para crédito {$credito->clave_contrato}. " .
            "Monto: \${$desembolso->monto_desembolsado} vía {$desembolso->forma_desembolso}"
        );

        // Notificar al ciudadano si tiene cuenta
        $solicitud = SolicitudCredito::where('credito_id', $credito->id)->first();
        if ($solicitud) {
            AnuncioCiudadano::create([
                'user_id' => $solicitud->user_id,
                'titulo'  => '¡Tu crédito ha sido desembolsado!',
                'mensaje' => "Hemos procesado el desembolso de tu crédito {$credito->clave_contrato} " .
                             "por $" . number_format($data['monto_desembolsado'], 2) .
                             " mediante {$data['forma_desembolso']} el " .
                             \Carbon\Carbon::parse($data['fecha_desembolso'])->format('d/m/Y') . '.',
                'tipo'       => 'exito',
                'url_accion' => route('portal.credito'),
            ]);
        }

        if ($solicitud) {
            $diasLimite = str_contains(strtolower($credito->modalidad?->nombre ?? ''), 'sustentable') ? 60 : 45;

            ComprobacionUso::create([
                'credito_id'                => $credito->id,
                'solicitud_id'              => $solicitud->id,
                'acreditado_id'             => $credito->acreditado_id,
                'fecha_desembolso'          => $desembolso->fecha_desembolso,
                'fecha_limite_comprobacion' => \Carbon\Carbon::parse($desembolso->fecha_desembolso)->addDays($diasLimite),
                'estatus'                   => 'Pendiente',
            ]);
        }

        return redirect()->route('acreditados.show', $credito->acreditado_id)
            ->with('success', 'Desembolso registrado correctamente.');
    }

    /**
     * Bloquea el desembolso si el monto del crédito supera el presupuesto
     * disponible de su modalidad para el ejercicio fiscal vigente.
     */
    private function verificarPresupuestoDisponible(Credito $credito): void
    {
        $anio = $credito->fecha_entrega?->year ?? now()->year;

        $presupuesto = PresupuestoPrograma::where('modalidad_id', $credito->modalidad_id)
            ->where('ejercicio_fiscal', $anio)
            ->first();

        if (!$presupuesto) return;

        $ejercido = Credito::where('modalidad_id', $credito->modalidad_id)
            ->where('estatus', '!=', 'Cancelado')
            ->whereYear('fecha_entrega', $anio)
            ->sum('monto_otorgado');

        $disponible = (float) $presupuesto->monto_autorizado - (float) $ejercido;
        $monto      = (float) $credito->monto_otorgado;

        abort_if($monto > $disponible, 422,
            'No se puede desembolsar: el monto del crédito ($' . number_format($monto, 2) .
            ') supera el presupuesto disponible ($' . number_format($disponible, 2) . ') para esta modalidad.'
        );

        if (round($disponible - $monto, 2) <= 0.01) {
            Log::warning("Presupuesto de la modalidad {$credito->modalidad_id} quedó en $0 tras el desembolso del crédito {$credito->clave_contrato} (ID {$credito->id}).");
        }
    }
}
