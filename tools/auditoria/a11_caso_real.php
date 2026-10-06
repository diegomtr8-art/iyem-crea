<?php
/**
 * A.11: crea en la base LOCAL el crédito AUD-A11-REAL, copia del caso CREA-2026-101 de
 * docs/REESTRUCTURACION.md: $30,000 a 24 meses, 7% ordinario, 17.5% moratorio, entrega
 * 2026-01-15, cuotas 1 a 3 pagadas a tiempo y 4 en adelante sin pagar.
 *
 * php tools/auditoria/a11_caso_real.php
 */

use App\Models\Acreditado;
use App\Models\Amortizacion;
use App\Models\Credito;
use App\Models\User;
use App\Services\CreditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

if (!app()->environment('local') || !in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'])) {
    fwrite(STDERR, "Solo corre contra la base local.\n");
    exit(1);
}

DB::transaction(function () {
    $viejos = Credito::where('clave_contrato', 'AUD-A11-REAL')->pluck('id');
    Amortizacion::whereIn('credito_id', $viejos)->delete();
    DB::table('reestructuraciones')->whereIn('credito_id', $viejos)->delete();
    Credito::whereIn('id', $viejos)->delete();

    $acreditado = Acreditado::firstOrCreate(
        ['nombre_completo' => 'AUDITORIA A11 (prueba)'],
        ['municipio' => 'Mérida']
    );

    $entrega = Carbon::parse('2026-01-15');
    $credito = Credito::create([
        'acreditado_id' => $acreditado->id,
        'modalidad_id' => 3, // Emprendedores
        'clave_contrato' => 'AUD-A11-REAL',
        'monto_otorgado' => 30000,
        'plazo_meses' => 24,
        'fecha_entrega' => $entrega->toDateString(),
        'tasa_interes_ordinario' => 7.00,
        'tasa_interes_moratorio' => 17.50,
        'estatus' => 'Moroso',
    ]);
    app(CreditService::class)->generarTablaAmortizacion($credito, 30000, 24, 7.00, $entrega->copy(), 'Emprendedores');

    // Cuotas 1 a 3 pagadas a tiempo, igual que las dejaría PagoController.
    foreach ($credito->amortizaciones()->where('numero_cuota', '<=', 3)->get() as $fila) {
        $fila->update([
            'capital_pagado' => $fila->capital_esperado,
            'interes_ordinario_pagado' => $fila->interes_ordinario_esperado,
            'saldo_insoluto' => round(max(0, $fila->saldo_insoluto - $fila->capital_esperado), 2),
            'pago_restante' => 0,
            'estado' => 'Pagado',
            'fecha_ultimo_pago' => $fila->fecha_vencimiento,
        ]);
    }

    echo "AUD-A11-REAL creado con id {$credito->id}\n";
});

// Usuario operativo de prueba para capturar las reestructuraciones desde la pantalla.
$auditor = User::updateOrCreate(
    ['email' => 'auditoria-a11@crea.test'],
    ['name' => 'Auditoría A11 (prueba)', 'tipo' => 'operativo', 'password' => Hash::make('a11-local-prueba'),
     'email_verified_at' => now()]
);
$auditor->syncRoles(['Administrador']);
echo "Usuario de prueba: {$auditor->email}\n";
