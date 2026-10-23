<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Credito;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Avisos de vencimiento del portal ciudadano. Solo lectura: no calcula ni
 * actualiza mora, estados ni pagos.
 */
class AvisosController extends Controller
{
    private const VENTANA_DIAS = 15;

    public function index(): Response
    {
        $diasGracia = (int) config('credito.dias_gracia_mora');

        $props = [
            'credito'      => null,
            'cuotas'       => [],
            'dias_gracia'  => $diasGracia,
            'ventana_dias' => self::VENTANA_DIAS,
        ];

        // El crédito sale solo de la solicitud del usuario en sesión.
        $solicitud = auth()->user()->solicitudCredito;
        if (!$solicitud || !$solicitud->credito_id) {
            return Inertia::render('portal/Avisos', $props);
        }

        $credito = Credito::findOrFail($solicitud->credito_id);
        $hoy     = Carbon::now('America/Merida')->startOfDay();

        $props['credito'] = ['clave_contrato' => $credito->clave_contrato];

        $props['cuotas'] = $credito->amortizaciones()
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->get()
            ->map(function ($a) use ($hoy, $diasGracia) {
                // El cast 'date' deja la fecha a las 00:00 en la zona de la app (UTC);
                // se reconstruye en Mérida para no perder un día al comparar con $hoy.
                $vencimiento = Carbon::parse($a->fecha_vencimiento->toDateString(), 'America/Merida')->startOfDay();
                // Carbon 3: con signo. Futuro > 0, hoy = 0, vencida < 0.
                $dias = (int) $hoy->diffInDays($vencimiento);

                // Mismo umbral que CreditService/UpdateMoratorio/PagoController: mora si atraso > dias_gracia_mora.
                $aviso = match (true) {
                    $dias < -$diasGracia         => 'vencida',
                    $dias < 0                    => 'gracia',
                    $dias <= self::VENTANA_DIAS  => 'proxima',
                    default                      => 'pendiente',
                };

                return [
                    'numero_cuota'      => $a->numero_cuota,
                    'fecha_vencimiento' => $a->fecha_vencimiento->format('d/m/Y'),
                    'monto'             => (float) $a->pago_restante,
                    'dias'              => $dias,
                    'aviso'             => $aviso,
                ];
            })
            ->values();

        return Inertia::render('portal/Avisos', $props);
    }
}
