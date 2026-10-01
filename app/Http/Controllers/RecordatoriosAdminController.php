<?php

namespace App\Http\Controllers;

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
        return Inertia::render('Admin/Recordatorios', ['dias_default' => 3]);
    }

    public function enviar(Request $request)
    {
        $data = $request->validate(['dias' => 'required|integer|min:1|max:30']);

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
            "Recordatorios encolados: {$encolados}. Ya enviados antes: {$yaEnviados}. Sin correo: {$sinCorreo}.");
    }
}