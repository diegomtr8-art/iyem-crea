<?php

namespace App\Http\Controllers;

use App\Console\Commands\RecordatoriosPago;
use App\Jobs\EnviarRecordatorioPago;
use App\Models\Amortizacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RecordatoriosAdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Recordatorios', ['dias_max' => RecordatoriosPago::DIAS_ANTICIPACION]);
    }

    public function enviar(Request $request)
    {
        // Nunca más lejos que el aviso diario: si el botón avisara antes, su marca bloquearía el aviso de 3 días
        $data = $request->validate([
            'dias' => 'required|integer|min:1|max:' . RecordatoriosPago::DIAS_ANTICIPACION,
        ]);

        $hoy   = Carbon::now('America/Merida')->startOfDay();
        $hasta = $hoy->copy()->addDays($data['dias']);

        $encolados = $yaEnviados = $sinCorreo = 0;

        Amortizacion::with('credito.acreditado')
            ->whereNotIn('estado', ['Pagado', 'Condonado', 'Reestructurada', 'Gracia'])
            ->whereBetween('fecha_vencimiento', [$hoy->toDateString(), $hasta->toDateString()])
            ->chunkById(200, function ($cuotas) use (&$encolados, &$yaEnviados, &$sinCorreo) {
                foreach ($cuotas as $cuota) {
                    if (! $cuota->credito?->acreditado?->correo) {
                        $sinCorreo++;
                        continue;
                    }

                    // Reserva atómica: solo la primera ejecución logra marcar la cuota.
                    // DB::table (no el modelo) para no tocar updated_at de la cuota.
                    $reservada = DB::table('amortizaciones')
                        ->where('id', $cuota->id)
                        ->whereNull('recordatorio_enviado_at')
                        ->update(['recordatorio_enviado_at' => now()]);

                    if ($reservada === 0) {
                        $yaEnviados++;
                        continue;
                    }

                    EnviarRecordatorioPago::dispatch($cuota);
                    $encolados++;
                }
            });

        return back()->with('success',
            "Recordatorios encolados: {$encolados}. Ya avisadas antes (no se reenvían): {$yaEnviados}. Sin correo: {$sinCorreo}.");
    }
}